import os
import sys
import json
import requests
import redis
import time

# --- IMPORTS PADRÃO DOCKER ---
# Mantemos os imports do shared para funcionar no container e evitar erro de 'ImportError'
from python_services.shared.conectar_banco import criar_conexao_db, get_docker_secret, redis_conteudo_results_pool
from python_services.content_ia.celery_app_conteudo import celery_app_conteudo

# Nome da fila de resultados (Igual ao anexo)
NOME_FILA_RESULTADOS_CONTEUDO = "fila_resultados_conteudo_ia"

# --- CHAVES DE API (SEGURANÇA ATIVADA) ---
# Usamos get_docker_secret para ler dos arquivos txt, mantendo a segurança
API_KEY_GOOGLE = get_docker_secret('google_api_key', os.getenv('API_KEY_GOOGLE'))
CX_ID_GOOGLE = os.getenv('CX_ID_GOOGLE')
GEMINI_API_KEY = get_docker_secret('gemini_api_key', os.getenv('GEMINI_API_KEY'))

GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent"

# Prompt idêntico ao anexo
PROMPT_SISTEMA_SEO = """
Você é um especialista em SEO para e-commerce. Sua tarefa é transformar um nome de produto longo de entrada (com códigos internos) num termo de pesquisa curto e eficaz que um cliente real usaria no Google.

REGRAS RÍGIDAS:
1. IDENTIFIQUE O PRODUTO E MODELO: Mantenha Nome, Marca e MODELO DE MARKETING (ex: "GSB 18V-65").
2. REMOVA CÓDIGOS INTERNOS: Remova agressivamente códigos de fabricante e SKUs inúteis.
3. TRADUZA ESPECIFICAÇÕES: Traduza "S/BAT" -> "Sem Bateria", etc.
4. FORMATE COMO PESQUISA: O resultado deve ser uma frase de pesquisa limpa.
5. Responda APENAS com o termo de pesquisa otimizado.
"""

# --- FUNÇÕES DE PERSISTÊNCIA ---

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

# --- FUNÇÕES AUXILIARES E LÓGICA DE IA ---

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
    
    max_tentativas = 3
    for tentativa in range(max_tentativas):
        try:
            res = requests.post(url, json=payload, timeout=30)
            if res.status_code == 200:
                resultado = res.json()
                if resultado.get('candidates') and resultado['candidates'][0].get('content'):
                    # LÓGICA DO ANEXO: Remove aspas extras que a IA às vezes coloca
                    return resultado['candidates'][0]['content']['parts'][0]['text'].strip().replace('"', '').replace("'", "")
            elif res.status_code == 503:
                print(f"AVISO: API Gemini Sobrecarregada (503). Tentativa {tentativa+1}. Esperando 5s...")
                time.sleep(5)
                continue
        except Exception as e:
            print(f"Erro req SEO: {e}")
            time.sleep(5)
            
    raise Exception("Falha na API Gemini SEO após tentativas.")

def buscar_google_api(query):
    if not API_KEY_GOOGLE or not CX_ID_GOOGLE: raise Exception("API Google não configurada")
    
    url = "https://www.googleapis.com/customsearch/v1"
    
    # Texto (10 resultados)
    r_txt = requests.get(url, params={'key': API_KEY_GOOGLE, 'cx': CX_ID_GOOGLE, 'q': query, 'num': 10})
    r_txt.raise_for_status()
    
    # Imagem (10 resultados - Lógica do anexo)
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
    
    # LÓGICA DO ANEXO: Retorna aviso se contexto for vazio
    if not ctx:
        return "Nenhum contexto encontrado no Google.", imgs
        
    return ctx, imgs

def chamar_api_gemini_final(prompt_sys, schema, palavra, contexto):
    url = f"{GEMINI_API_URL}?key={GEMINI_API_KEY}"
    
    # LÓGICA DO ANEXO: Prompt mais detalhado para lidar com campos de objeto
    prompt_user = f"""
    NOME DO PRODUTO: {palavra}
    CONTEXTO COLETADO (TEXTO DOS CONCORRENTES):
    {contexto}
    Com base APENAS no nome do produto e no contexto de texto acima, gere o JSON solicitado. 
    Se algum campo for um objeto sem propriedades definidas, preencha como texto.
    """
    
    payload = {
        "contents": [{"parts": [{"text": prompt_user}]}],
        "systemInstruction": {"parts": [{"text": prompt_sys}]},
        "generationConfig": {
            "responseMimeType": "application/json",
            "responseSchema": schema,
            "temperature": 0.7
        }
    }
    
    max_tentativas = 3
    for tentativa in range(max_tentativas):
        try:
            res = requests.post(url, json=payload, timeout=120)
            if res.status_code == 200:
                json_texto = res.json()['candidates'][0]['content']['parts'][0]['text']
                # Valida se é JSON válido
                json.loads(json_texto)
                return json_texto
            elif res.status_code == 503:
                print(f"AVISO: API Gemini Final (503). Tentativa {tentativa+1}. Esperando 10s...")
                time.sleep(10)
            else:
                raise Exception(f"Erro Gemini: {res.text}")
        except Exception as e:
            print(f"Erro tentativa {tentativa}: {e}")
            time.sleep(5)
            
    raise Exception("Falha na API Gemini Final.")

def enviar_resultado_redis(dados):
    try:
        # Usa o pool importado do shared/conectar_banco (Docker friendly)
        r = redis.Redis(connection_pool=redis_conteudo_results_pool)
        r.lpush(NOME_FILA_RESULTADOS_CONTEUDO, json.dumps(dados))
        r.close()
        print(f"INFO: Resultado enviado para Redis.")
    except Exception as e:
        print(f"ERRO CRÍTICO REDIS: {e}")

# --- TAREFA CELERY ---
@celery_app_conteudo.task(name='python_services.content_ia.worker_conteudo.tarefa_gerar_conteudo', bind=True)
def tarefa_gerar_conteudo(self, item):
    pid = item.get('id')
    print(f"--- [WORKER] Iniciando Job ID {pid} ---")
    resultado = None

    try:
        # 1. Configuração
        print(f"INFO [Worker {pid}]: Buscando template {item['id_template_ia']}...")
        template = buscar_template_db(item['id_template_ia'])
        
        # 2. SEO
        print(f"INFO [Worker {pid}]: Otimizando SEO...")
        seo = chamar_ia_seo(item['palavra_chave_entrada'])
        print(f"INFO [Worker {pid}]: SEO Definido: '{seo}'")
        salvar_seo_db(pid, seo)
        
        # 3. Google
        print(f"INFO [Worker {pid}]: Buscando Google...")
        j_txt, j_img = buscar_google_api(seo)
        salvar_cache_google(pid, 'texto', j_txt)
        salvar_cache_google(pid, 'imagem', j_img)
        ctx, urls = preparar_contexto_google(j_txt, j_img)
        
        # 4. Geração Final
        print(f"INFO [Worker {pid}]: Gerando JSON Final (IA)...")
        json_str = chamar_api_gemini_final(template['prompt_sistema'], template['json_schema_saida_obj'], seo, ctx)
        
        obj_final = json.loads(json_str)
        obj_final['imagens_encontradas_google'] = urls
        obj_final['termo_seo_usado'] = seo
        
        resultado = {
            "status": "sucesso",
            "produto_id": pid,
            "id_template_ia": item['id_template_ia'],
            "json_gerado": json.dumps(obj_final, ensure_ascii=False),
            "modelo_usado": "gemini-2.5-flash",
            # Passamos de volta o ID da fila se vier no payload (para o coletor dar baixa)
            "id_tarefa_fila": item.get('id_tarefa_fila') 
        }
        print(f"--- [WORKER] SUCESSO ID {pid} ---")

    except Exception as e:
        print(f"--- [WORKER] FALHA ID {pid}: {e} ---")
        resultado = {
            "status": "falha",
            "produto_id": pid,
            "mensagem_erro": str(e),
            "id_tarefa_fila": item.get('id_tarefa_fila')
        }
    
    finally:
        if resultado:
            enviar_resultado_redis(resultado)