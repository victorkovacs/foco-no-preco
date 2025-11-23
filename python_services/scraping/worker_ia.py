import os
import redis
import json
import time
import datetime
import google.generativeai as genai
from celery.exceptions import SoftTimeLimitExceeded

# --- IMPORTS PADRÃO DOCKER ---
from python_services.scraping.celery_app import celery_app, redis_ia_results_pool
from python_services.shared.conectar_banco import criar_conexao_db

GEMINI_API_KEY = os.environ.get('GEMINI_API_KEY')
if GEMINI_API_KEY:
    genai.configure(api_key=GEMINI_API_KEY)

MODELO_GEMINI = 'gemini-2.5-flash'

def salvar_log_tokens(id_org, t_in, t_out):
    try:
        conn = criar_conexao_db()
        if conn:
            c = conn.cursor()
            c.execute(
                "INSERT INTO LogIaTokens (id_organizacao, data_uso, modelo, tokens_entrada, tokens_saida, custo_estimado_usd) VALUES (%s, NOW(), %s, %s, %s, 0.0)",
                (id_org, MODELO_GEMINI, t_in, t_out)
            )
            conn.commit()
            conn.close()
    except:
        pass

@celery_app.task(name='python_services.scraping.worker_ia.tarefa_match_ia', bind=True, queue='fila_ia')
def tarefa_match_ia(self, dados):
    sku = dados.get('produto_sku')
    nome = dados.get('nome_produto')
    candidatos = dados.get('candidatos', [])
    id_org = dados.get('id_organizacao')

    print(f"[WORKER IA] Analisando {sku} vs {len(candidatos)} candidatos...")

    texto_candidatos = "\n".join([f"ID: {c['id']} | Título: {c['titulo']} | Link: {c['url']}" for c in candidatos])
    
    prompt = f"""
    PRODUTO ALVO: "{nome}" (SKU: {sku})
    CANDIDATOS:
    {texto_candidatos}
    
    TAREFA: Qual candidato é o mesmo produto?
    RESPOSTA JSON: {{"id": ID_DO_VENCEDOR}} ou {{"id": null}}
    """

    try:
        model = genai.GenerativeModel(MODELO_GEMINI)
        resp = model.generate_content(prompt)
        
        # Tenta pegar uso de tokens (nem sempre disponível dependendo da versão da lib)
        try:
            usage = resp.usage_metadata
            salvar_log_tokens(id_org, usage.prompt_token_count, usage.candidates_token_count)
        except:
            pass

        limpo = resp.text.replace('```json', '').replace('```', '').strip()
        resultado = json.loads(limpo)
        vencedor_id = resultado.get('id')

        if vencedor_id:
            print(f"✅ [WORKER IA] Match encontrado: ID {vencedor_id}")
            final = {
                "id_produto": dados['id_produto'],
                "id_link_externo": vencedor_id,
                "id_organizacao": id_org,
                "match_data": datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            # Envia para Redis (db=2)
            r = redis.Redis(connection_pool=redis_ia_results_pool)
            r.lpush('fila_ia_resultados', json.dumps(final))
            r.close()
        else:
            print("⚠️ [WORKER IA] Nenhum match.")

        time.sleep(2) # Pausa leve

    except Exception as e:
        print(f"[WORKER IA] Erro: {e}")