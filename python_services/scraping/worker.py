import requests
import re
import datetime
import json
import redis
from bs4 import BeautifulSoup
from celery.exceptions import SoftTimeLimitExceeded

# Importação absoluta correta
from python_services.scraping.celery_app import celery_app, redis_results_pool

# Configurações para evitar bloqueios simples
USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
]

def limpar_preco(texto_preco):
    """Converte 'R$ 1.200,50' para float 1200.50"""
    if not texto_preco:
        return None
    try:
        # Mantém apenas números, pontos e vírgulas
        limpo = re.sub(r'[^\d,.]', '', str(texto_preco))
        
        # Lógica para tratar milhar(.) e decimal(,)
        if ',' in limpo and '.' in limpo:
            limpo = limpo.replace('.', '').replace(',', '.') # 1.000,00 -> 1000.00
        elif ',' in limpo:
            limpo = limpo.replace(',', '.') # 1000,00 -> 1000.00
            
        return float(limpo)
    except ValueError:
        return None

@celery_app.task(name='python_services.scraping.worker.tarefa_scrape', bind=True, queue='fila_scrape', max_retries=3)
def tarefa_scrape(self, alvo):
    id_alvo = alvo.get('id_alvo')
    url = alvo.get('link_a_usar')
    seletor = alvo.get('SeletorPreco')
    
    print(f"[WORKER] Processando Alvo {id_alvo} | URL: {url}")

    if not url or not seletor:
        print(f"[WORKER] ❌ Dados inválidos para alvo {id_alvo}")
        return

    headers = {
        'User-Agent': USER_AGENTS[id_alvo % len(USER_AGENTS)]
    }

    try:
        response = requests.get(url, headers=headers, timeout=20, verify=False)
        response.raise_for_status()

        soup = BeautifulSoup(response.content, 'html.parser')
        elemento = soup.select_one(seletor)
        
        preco_final = 0.0
        
        if elemento:
            texto = elemento.get_text(strip=True)
            preco_limpo = limpar_preco(texto)
            
            if preco_limpo:
                # Aplica desconto se existir
                desconto = float(alvo.get('PercentualDescontoAVista', 0) or 0)
                preco_final = preco_limpo * (1 - (desconto / 100))
                print(f"✅ [WORKER] Preço capturado: R$ {preco_final:.2f}")
            else:
                print(f"⚠️ [WORKER] Elemento achado mas preço ilegível: '{texto}'")
        else:
            print(f"⚠️ [WORKER] Seletor '{seletor}' não encontrou nada.")

        # Envia para o Redis se achou preço
        if preco_final > 0:
            payload = {
                'id_alvo': id_alvo,
                'id_organizacao': alvo.get('id_organizacao'),
                'id_link_externo': alvo.get('id_link_externo'),
                'sku': alvo.get('sku'),
                'ID_Vendedor': alvo.get('ID_Vendedor'),
                'preco': preco_final,
                'data_extracao': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            
            try:
                r = redis.Redis(connection_pool=redis_results_pool)
                r.lpush('fila_resultados', json.dumps(payload))
            except Exception as e_redis:
                print(f"❌ [WORKER] Erro ao enviar para Redis: {e_redis}")

    except requests.RequestException as e:
        print(f"❌ [WORKER] Erro de conexão: {e}")
        self.retry(exc=e, countdown=60)
    except Exception as e:
        print(f"❌ [WORKER] Erro genérico: {e}")