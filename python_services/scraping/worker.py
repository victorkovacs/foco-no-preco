import requests
import re
import time
import datetime
import random
import json
import redis
from bs4 import BeautifulSoup
from celery.exceptions import SoftTimeLimitExceeded

# Importação absoluta correta para o ambiente do projeto
from python_services.scraping.celery_app import celery_app, redis_results_pool

# --- IMPORTANTE: Importa a Classe Base de DLQ ---
from python_services.shared.celery_task_dlq import CeleryDLQTask

# --- Configurações e Constantes ---

USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.3 Safari/605.1.15',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36'
]

# --- Funções Auxiliares de Limpeza e Extração ---

def limpeza_bruta(texto_preco_com_multiplos):
    """
    Limpa o texto do preço removendo R$, espaços e corrigindo duplicações comuns
    em scraping (ex: '10001000' -> '1000').
    """
    if not texto_preco_com_multiplos:
        return None

    # Se vierem múltiplos textos (ex: preço;parcelas), pega só o primeiro
    partes = texto_preco_com_multiplos.split(';')
    texto_preco = partes[0].strip()

    if not texto_preco:
        return None

    s_original = str(texto_preco)
    price_to_process = s_original

    try:
        # --- ETAPA 1: Pré-limpeza básica ---
        pre_cleaned = price_to_process.replace("R$", "").strip() # Ex: "125,44Em até 6x..."

        # --- ETAPA 2: Extrair apenas o padrão de preço (números, . e ,) ---
        match = re.search(r'[\d.,]+', pre_cleaned)
        if match:
            price_to_process = match.group(0) # Ex: "125,44"
        else:
            price_to_process = re.sub(r'[^\d.,]', '', pre_cleaned) 

        # --- ETAPA 3: DETECÇÃO DE DUPLICAÇÃO DE TEXTO ---
        # Alguns sites renderizam o preço duas vezes no mesmo elemento (oculta/visivel)
        digits_only = re.sub(r'\D', '', price_to_process) 
        n_digits = len(digits_only) 

        if n_digits > 0 and n_digits % 2 == 0:
            half_n_digits = n_digits // 2
            first_half_digits = digits_only[:half_n_digits]
            second_half_digits = digits_only[half_n_digits:]

            if first_half_digits == second_half_digits:
                half_n_original = len(price_to_process) // 2
                price_to_process = price_to_process[:half_n_original]
        
        # Remove caracteres indesejados finais
        return price_to_process.strip() 

    except Exception as e:
        print(f"  -> [ERRO LIMPEZA BRUTA] Erro inesperado ao limpar '{s_original}': {e}")
        return None

def extrair_preco_com_seletor(soup, seletor_css):
    """
    Usa BeautifulSoup para encontrar elementos pelo seletor CSS.
    Retorna o texto do primeiro encontro válido ou concatena se necessário.
    """
    try:
        elementos = soup.select(seletor_css) 
        if not elementos: return None 
        textos = [elem.get_text(strip=True) for elem in elementos]
        textos_validos = [t for t in textos if t] 
        if not textos_validos: return None 
        resultado = ";".join(textos_validos)
        return resultado
    except Exception as e:
        print(f"    -> [AVISO] Erro ao aplicar seletor '{seletor_css}': {e}")
        return None

# --- Tarefa Celery ---

@celery_app.task(
    name='python_services.scraping.worker.tarefa_scrape', 
    bind=True, 
    queue='fila_scrape', 
    max_retries=3,          # Tenta 3 vezes
    base=CeleryDLQTask      # <--- ATIVA A DLQ NA FALHA FINAL
)
def tarefa_scrape(self, alvo_dict):
        
    # Extrai os dados do dicionário da tarefa
    id_alvo = alvo_dict.get('id_alvo')
    sku = alvo_dict.get('sku')
    # Tenta pegar 'NomeVendedor' ou fallback para evitar erro se a chave mudar
    nome_vendedor = alvo_dict.get('NomeVendedor', f"Vendedor_{alvo_dict.get('ID_Vendedor')}")
    link_a_usar = alvo_dict.get('link_a_usar')
    seletor = alvo_dict.get('SeletorPreco')
    
    print(f"[WORKER] Iniciando Alvo {id_alvo}: {sku} @ {nome_vendedor}")
    
    try:
        if not link_a_usar or not seletor:
            print(f"[WORKER] Alvo {id_alvo} pulado (link ou seletor nulo).")
            return 

        # Simula uma pausa humana (1 a 3 segundos)
        time.sleep(random.uniform(1.0, 3.0))
        
        headers = { 'User-Agent': random.choice(USER_AGENTS) }
        
        # Requisição
        response = requests.get(
            link_a_usar, 
            headers=headers, 
            timeout=30,
            verify=False # Ignora erros de SSL
        ) 
        response.raise_for_status() # Levanta exceção para 4xx e 5xx
        
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # 1. Extração Bruta
        preco_bruto = extrair_preco_com_seletor(soup, seletor)
        if not preco_bruto:
            print(f"[WORKER] Alvo {id_alvo}: Seletor não encontrou nada no HTML.")
            # Se não achou o seletor, pode ser que o site mudou o layout.
            return
            
        # 2. Limpeza de String
        valor_limpo_string = limpeza_bruta(preco_bruto)
        if not valor_limpo_string:
            print(f"[WORKER] Alvo {id_alvo}: Falha na limpeza de preço (Bruto: '{preco_bruto}').")
            return

        # 3. Conversão para Float
        # Remove tudo que não for dígito para fazer a divisão
        valor_limpo_digits_only = re.sub(r'\D', '', valor_limpo_string)
        if not valor_limpo_digits_only:
            print(f"[WORKER] Alvo {id_alvo}: Falha ao extrair dígitos (Limpo: '{valor_limpo_string}').")
            return
            
        # Assume que os dois últimos dígitos são centavos
        valor_base_float = float(valor_limpo_digits_only) / 100.0
        
        # 4. Cálculo de Desconto à Vista
        percentual_desconto = float(alvo_dict.get('PercentualDescontoAVista', 0.0) or 0.0)
        valor_final = valor_base_float
        if 0.0 < percentual_desconto <= 100.0:
            fator = 1.0 - (percentual_desconto / 100.0)
            valor_final = valor_base_float * fator

        # --- SUCESSO! PREPARA O RESULTADO ---
        
        resultado = {
            "id_alvo": id_alvo,
            "id_organizacao": alvo_dict.get('id_organizacao'),
            "id_link_externo": alvo_dict.get('id_link_externo'),
            "sku": sku,
            "ID_Vendedor": alvo_dict.get('ID_Vendedor'),
            "preco": round(valor_final, 2),
            "data_extracao": datetime.datetime.now(datetime.timezone.utc).isoformat()
        }
        
        # Envia para a Fila de Resultados (Redis)
        r_conn = None
        try:
            r_conn = redis.Redis(connection_pool=redis_results_pool)
            r_conn.lpush('fila_resultados', json.dumps(resultado))
            print(f"[WORKER] SUCESSO Alvo {id_alvo}: R$ {valor_final:.2f} (Bruto: {valor_base_float})")
        except Exception as e_redis:
            print(f"[WORKER] ERRO REDIS Alvo {id_alvo}: {e_redis}")
        finally:
            if r_conn:
                r_conn.close()

    # --- Tratamento de Erros ---
    except SoftTimeLimitExceeded:
        print(f"[WORKER] ERRO Alvo {id_alvo}: Timeout do Celery. Tarefa abortada.")
        
    except requests.exceptions.HTTPError as e:
        status_code = e.response.status_code
        print(f"[WORKER] ERRO HTTP {status_code} no Alvo {id_alvo}.")
        
        if 400 <= status_code < 500:
            return 
        else:
            self.retry(exc=e, countdown=60*5) 

    except requests.exceptions.RequestException as e:
        print(f"[WORKER] ERRO de Rede no Alvo {id_alvo}: {e}")
        self.retry(exc=e, countdown=60*15)

    except Exception as e:
        print(f"[WORKER] ERRO Inesperado no Alvo {id_alvo}: {e}")
        return