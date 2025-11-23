import requests
import re
import time
import datetime
import json
import redis
from bs4 import BeautifulSoup
from celery import Celery
from celery.exceptions import SoftTimeLimitExceeded

# --- AJUSTE DE IMPORTAÇÃO (Padrão Docker) ---
from python_services.scraping.celery_app import celery_app, redis_results_pool

# Lista de User-Agents para rotação (Evita bloqueios)
USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
]

def limpar_preco(texto_preco):
    """
    Recebe uma string suja (ex: 'R$ 1.299,90\n') e retorna um float (1299.90).
    """
    if not texto_preco:
        return None
    
    # Remove tudo que não é número, vírgula ou ponto
    # Mantém apenas dígitos, ',' e '.'
    limpo = re.sub(r'[^\d,.]', '', str(texto_preco))
    
    # Exemplo: 1.299,90 -> Remove ponto -> 1299,90 -> Troca vírgula por ponto -> 1299.90
    try:
        if ',' in limpo and '.' in limpo:
            limpo = limpo.replace('.', '').replace(',', '.')
        elif ',' in limpo:
            limpo = limpo.replace(',', '.')
            
        return float(limpo)
    except ValueError:
        return None

@celery_app.task(name='tarefa_scrape', bind=True, queue='fila_scrape', max_retries=3)
def tarefa_scrape(self, alvo):
    """
    Tarefa Celery que acessa a URL e extrai o preço.
    """
    id_alvo = alvo.get('id_alvo')
    url = alvo.get('link_a_usar')
    seletor = alvo.get('SeletorPreco')
    id_vendedor = alvo.get('ID_Vendedor')
    
    print(f"[WORKER] Iniciando Alvo {id_alvo} | Vendedor {id_vendedor} | URL: {url}")

    if not url or not seletor:
        print(f"[WORKER] ERRO: URL ou Seletor inválidos para o alvo {id_alvo}.")
        return

    headers = {
        'User-Agent': USER_AGENTS[id_alvo % len(USER_AGENTS)] # Rotação simples baseada no ID
    }

    try:
        # 1. Faz a requisição HTTP
        response = requests.get(url, headers=headers, timeout=30, verify=False)
        response.raise_for_status() # Lança erro se for 404, 500, etc.

        # 2. Parse do HTML
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # 3. Busca o elemento do preço
        elemento_preco = soup.select_one(seletor)
        
        preco_encontrado = 0.0
        if elemento_preco:
            texto_bruto = elemento_preco.get_text(strip=True)
            preco_limpo = limpar_preco(texto_bruto)
            
            if preco_limpo:
                preco_encontrado = preco_limpo
                # Aplica desconto à vista se houver
                desconto = alvo.get('PercentualDescontoAVista', 0.0)
                if desconto and desconto > 0:
                    preco_encontrado = preco_encontrado * (1 - (desconto / 100))
                    
                print(f"✅ [WORKER] Preço encontrado: R$ {preco_encontrado:.2f}")
            else:
                print(f"⚠️ [WORKER] Seletor achou elemento, mas falhou ao converter preço: '{texto_bruto}'")
        else:
            print(f"⚠️ [WORKER] Preço não encontrado com o seletor: {seletor}")

        # 4. Envia resultado para o Redis (para o Coletor salvar)
        if preco_encontrado > 0:
            resultado = {
                'id_alvo': id_alvo,
                'id_organizacao': alvo.get('id_organizacao'),
                'id_link_externo': alvo.get('id_link_externo'),
                'sku': alvo.get('sku'),
                'ID_Vendedor': id_vendedor,
                'preco': preco_encontrado,
                'data_extracao': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            
            # Conecta ao Redis de Resultados (Pool do Celery App)
            try:
                r_conn = redis.Redis(connection_pool=redis_results_pool)
                r_conn.lpush('fila_resultados', json.dumps(resultado))
                print(f"-> [WORKER] Resultado enviado para fila.")
            except Exception as e_redis:
                print(f"ERRO [WORKER] Falha ao enviar para Redis: {e_redis}")

    except SoftTimeLimitExceeded:
        print(f"[WORKER] Timeout no alvo {id_alvo}.")
    except requests.RequestException as e:
        print(f"[WORKER] Erro de Rede no alvo {id_alvo}: {e}")
        # Tenta novamente em caso de erro de rede
        self.retry(exc=e, countdown=60)
    except Exception as e:
        print(f"[WORKER] Erro desconhecido no alvo {id_alvo}: {e}")