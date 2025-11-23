import os
import sys
import json
import requests
import redis
import time

# --- IMPORTS PADRÃO DOCKER ---
from python_services.shared.conectar_banco import criar_conexao_db
from python_services.content_ia.celery_app_conteudo import celery_app_conteudo, redis_conteudo_results_pool

# Nome da fila de resultados
NOME_FILA_RESULTADOS_CONTEUDO = "fila_resultados_conteudo_ia"

# --- CHAVES DE API (Lidas do .env) ---
API_KEY_GOOGLE = os.getenv('API_KEY_GOOGLE')
CX_ID_GOOGLE = os.getenv('CX_ID_GOOGLE')
GEMINI_API_KEY = os.getenv('GEMINI_API_KEY')
GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent"

PROMPT_SISTEMA_SEO = """
Você é um especialista em SEO para e-commerce. Sua tarefa é transformar um nome de produto longo de entrada (com códigos internos) num termo de pesquisa curto e eficaz que um cliente real usaria no Google.

REGRAS RÍGIDAS:
1. IDENTIFIQUE O PRODUTO E MODELO: Mantenha Nome, Marca e MODELO DE MARKETING (ex: "GSB 18V-65").
2. REMOVA CÓDIGOS INTERNOS: Remova agressivamente códigos de fabricante e SKUs inúteis.
3. TRADUZA ESPECIFICAÇÕES: Traduza "S/BAT" -> "Sem Bateria", etc.
4. FORMATE COMO PESQUISA: O resultado deve ser uma frase de pesquisa limpa.
5. Responda APENAS com o termo de pesquisa otimizado.
"""

# --- FUNÇÕES DE PERSISTÊNCIA IMEDIATA ---
def salvar_seo_db(produto_id, palavra_seo):
    conn = None
    try:
        conn = criar_conexao_db()
        if conn:
            cursor = conn.cursor()
            sql = "UPDATE produtos SET palavra_chave_seo = %s WHERE id = %s"
            cursor.execute(sql, (palavra_seo, produto_id))
            conn.commit()
            print(f"DB [Worker {produto_id}]: SEO salvo no banco.")
    except Exception as e:
        print(f"ERRO DB [Worker {produto_id}]: Falha ao salvar SEO. {e}")
    finally:
        if conn and conn.is_connected():
            conn.close()

def salvar_cache_google(produto_id, tipo_busca, json_dados):
    conn = None
    try:
        conn = criar_conexao_db()
        if conn:
            cursor = conn.cursor()
            json_string = json.dumps(json_dados, ensure_ascii=False)
            sql = """
                INSERT INTO cache_api_google (produto_id, tipo_busca, resposta_bruta_json) 
                VALUES (%s, %s, %s)
            """
            cursor.execute(sql, (produto_id, tipo_busca, json_string))
            conn.commit()
            print(f"DB [Worker {produto_id}]: Cache Google ({tipo_busca}) salvo.")
    except Exception as e:
        print(f"ERRO DB [Worker {produto_id}]: Falha ao salvar Cache Google. {e}")
    finally:
        if conn and conn.is_connected():
            conn.close()

# --- FUNÇÕES AUXILIARES ---
def limpar_schema_para_gemini(obj):
    if isinstance(obj, dict):
        obj.pop('additionalProperties', None)
        if obj.get('type') == 'object':
            if 'properties' not in obj or not obj['properties']:
                obj['type'] = 'string' 
                obj.pop('properties', None)
        for key, value in obj.items():
            limpar_schema_para_gemini(value)
    elif isinstance(obj, list):
        for item in obj:
            limpar_schema_para_gemini(item)
    return obj

def buscar_template_db(id_template_ia):
    conn = None
    try:
        conn = criar_conexao_db()
        if not conn: raise Exception("Falha na conexão DB")
        
        cursor = conn.cursor(dictionary=True)
        sql = "SELECT prompt_sistema, json_schema_saida FROM templates_ia WHERE id = %s"
        cursor.execute(sql, (id_template_ia,))
        template = cursor.fetchone()
        
        if not template:
            raise Exception(f"Template ID {id_template_ia} não encontrado.")
            
        schema_obj = json.loads(template['json_schema_saida'])
        schema_obj = limpar_schema_para_gemini(schema_obj)
        template['json_schema_saida_obj'] = schema_obj
        return template
    finally:
        if conn and conn.is_connected():
            conn.close()

def chamar_ia_seo(palavra_chave_entrada):
    if not GEMINI_API_KEY: raise Exception("GEMINI_API_KEY não configurada")
    
    url = f"{GEMINI_API_URL}?key={GEMINI_API_KEY}"
    payload = {
        "contents": [{"parts": [{"text": palavra_chave_entrada}]}],
        "systemInstruction": {"parts": [{"text": PROMPT_SISTEMA_SEO}]},
        "generationConfig": {"temperature": 0.2}
    }
    
    for i in range(3):
        try:
            res = requests.post(url, json=payload, timeout=30)
            if res.status_code == 200:
                return res.json()['candidates'][0]['content']['parts'][0]['text'].strip()
            time.sleep(5)
        except:
            time.sleep(5)
    raise Exception("Falha na API Gemini SEO.")

def buscar_google_api(query):
    if not API_KEY_GOOGLE or not CX_ID_GOOGLE: raise Exception("API Google não configurada")
    
    url = "https://www.googleapis.com/customsearch/v1"
    
    # Texto
    r_txt = requests.get(url, params={'key': API_KEY_GOOGLE, 'cx': CX_ID_GOOGLE, 'q': query, 'num': 10})
    r_txt.raise_for_status()
    
    # Imagem
    r_img = requests.get(url, params={'key': API_KEY_GOOGLE, 'cx': CX_ID_GOOGLE, 'q': query, 'num': 10, 'searchType': 'image'})
    r_img.raise_for_status()
    
    return r_txt.json(), r_img.json()

def preparar_contexto_google(json_texto, json_imagem):
    ctx = ""
    imgs = []
    for item in json_texto.get('items', []):
        ctx += f"Título: {item.get('title','')}\nSnippet: {item.get('snippet','')}\n\n"
    for item in json_imagem.get('items', []):
        if item.get('link'): imgs.append(item.get('link'))
    return ctx, imgs

def chamar_api_gemini_final(prompt_sys, schema, palavra, contexto):
    url = f"{GEMINI_API_URL}?key={GEMINI_API_KEY}"
    prompt_user = f"PRODUTO: {palavra}\nCONTEXTO:\n{contexto}\nGere o JSON solicitado."
    
    payload = {
        "contents": [{"parts": [{"text": prompt_user}]}],
        "systemInstruction": {"parts": [{"text": prompt_sys}]},
        "generationConfig": {
            "responseMimeType": "application/json",
            "responseSchema": schema,
            "temperature": 0.7
        }
    }
    
    for i in range(3):
        try:
            res = requests.post(url, json=payload, timeout=120)
            if res.status_code == 200:
                return res.json()['candidates'][0]['content']['parts'][0]['text']
            elif res.status_code == 503:
                time.sleep(10)
            else:
                raise Exception(f"Erro Gemini: {res.text}")
        except Exception as e:
            print(f"Erro tentativa {i}: {e}")
            time.sleep(5)
    raise Exception("Falha na API Gemini Final.")

def enviar_resultado_redis(dados):
    try:
        r = redis.Redis(connection_pool=redis_conteudo_results_pool)
        r.lpush(NOME_FILA_RESULTADOS_CONTEUDO, json.dumps(dados))
        r.close()
    except Exception as e:
        print(f"ERRO REDIS: {e}")

# --- TAREFA CELERY ---
@celery_app_conteudo.task(name='python_services.content_ia.worker_conteudo.tarefa_gerar_conteudo', bind=True)
def tarefa_gerar_conteudo(self, item):
    pid = item.get('id')
    print(f"--- [WORKER] Iniciando Produto {pid} ---")
    resultado = None

    try:
        # 1. Configuração
        template = buscar_template_db(item['id_template_ia'])
        
        # 2. SEO
        print(f"INFO [{pid}]: Otimizando SEO...")
        seo = chamar_ia_seo(item['palavra_chave_entrada'])
        salvar_seo_db(pid, seo)
        
        # 3. Google
        print(f"INFO [{pid}]: Buscando Google...")
        j_txt, j_img = buscar_google_api(seo)
        salvar_cache_google(pid, 'texto', j_txt)
        salvar_cache_google(pid, 'imagem', j_img)
        ctx, urls = preparar_contexto_google(j_txt, j_img)
        
        # 4. Geração Final
        print(f"INFO [{pid}]: Gerando Conteúdo...")
        json_str = chamar_api_gemini_final(template['prompt_sistema'], template['json_schema_saida_obj'], seo, ctx)
        
        obj_final = json.loads(json_str)
        obj_final['imagens_encontradas_google'] = urls
        obj_final['termo_seo_usado'] = seo
        
        resultado = {
            "status": "sucesso",
            "produto_id": pid,
            "id_template_ia": item['id_template_ia'],
            "json_gerado": json.dumps(obj_final, ensure_ascii=False),
            "modelo_usado": "gemini-2.5-flash"
        }
        print(f"✅ [WORKER] Sucesso Produto {pid}")

    except Exception as e:
        print(f"❌ [WORKER] Erro Produto {pid}: {e}")
        resultado = {
            "status": "falha",
            "produto_id": pid,
            "mensagem_erro": str(e)
        }
    
    finally:
        if resultado:
            enviar_resultado_redis(resultado)