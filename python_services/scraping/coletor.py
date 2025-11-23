import redis
import json
import time
import datetime
import sys
import mysql.connector

# --- AJUSTE DE IMPORTAÇÃO (Padrão Docker) ---
from python_services.scraping.celery_app import redis_results_pool
from python_services.shared.conectar_banco import criar_conexao_db

# --- CONFIGURAÇÕES ---
TAMANHO_LOTE = 500
TEMPO_LIMITE_LOTE_SEGUNDOS = 10 
NOME_FILA_RESULTADOS = 'fila_resultados' # Fila db=1

def salvar_lote_no_db(lista_de_tuplas):
    """
    Conecta ao MySQL e insere uma lista de tuplas 
    na tabela 'concorrentes' usando executemany().
    """
    if not lista_de_tuplas:
        return 0

    conn_db = None
    try:
        conn_db = criar_conexao_db()
        if conn_db is None:
            print("ERRO [COLETOR]: Falha ao conectar no banco para salvar lote.")
            return -1

        cursor = conn_db.cursor()
        
        # Query completa recuperada do seu arquivo original
        query_insert = """
        INSERT INTO concorrentes 
        (id_alvo, id_organizacao, id_link_externo, sku, ID_Vendedor, preco, data_extracao)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        
        cursor.executemany(query_insert, lista_de_tuplas)
        conn_db.commit()
        
        qtd_inseridos = cursor.rowcount
        cursor.close()
        conn_db.close()
        
        return qtd_inseridos

    except mysql.connector.Error as err:
        print(f"ERRO SQL [COLETOR]: {err}")
        if conn_db and conn_db.is_connected():
            conn_db.rollback() # Desfaz se der erro no meio
            conn_db.close()
        return -1

    except Exception as e:
        print(f"ERRO GENÉRICO [COLETOR] ao salvar no DB: {e}")
        if conn_db and conn_db.is_connected():
            conn_db.close()
        return -1

def main():
    print("[COLETOR] Iniciando serviço de persistência (Preços)...")
    
    # Conecta ao Redis usando o Pool definido no celery_app
    try:
        r_conn = redis.Redis(connection_pool=redis_results_pool)
    except Exception as e:
        print(f"ERRO FATAL [COLETOR]: Não foi possível conectar ao Redis. {e}")
        return

    while True:
        try:
            # 1. Tenta pegar um lote de resultados do Redis
            # Usa pipeline para ser atômico (pega e remove da fila)
            pipeline = r_conn.pipeline()
            pipeline.lrange(NOME_FILA_RESULTADOS, 0, TAMANHO_LOTE - 1)
            pipeline.ltrim(NOME_FILA_RESULTADOS, TAMANHO_LOTE, -1)
            resultados = pipeline.execute()
            
            lista_json = resultados[0] # O lrange retorna uma lista
            
            # Se a fila estiver vazia, espera um pouco e tenta de novo
            if not lista_json:
                time.sleep(1)
                continue

            print(f"--- [COLETOR] Processando lote de {len(lista_json)} itens... ---")
            
            # 2. Converte JSON para Tuplas do Python
            lista_de_tuplas = []
            for item_json in lista_json:
                try:
                    dados = json.loads(item_json)
                    
                    # Prepara a tupla na ordem exata da tabela
                    tupla = (
                        dados.get('id_alvo'),
                        dados.get('id_organizacao'),
                        dados.get('id_link_externo'),
                        dados.get('sku'),
                        dados.get('ID_Vendedor'),
                        dados.get('preco'),
                        dados.get('data_extracao')
                    )
                    lista_de_tuplas.append(tupla)
                except json.JSONDecodeError:
                    print(f"ERRO [COLETOR]: JSON inválido encontrado: {item_json}")
                except Exception as e_parse:
                    print(f"ERRO [COLETOR]: Falha ao processar item: {e_parse}")

            # 3. Salva no Banco de Dados
            if lista_de_tuplas:
                qtd = salvar_lote_no_db(lista_de_tuplas)
                if qtd > 0:
                    print(f"✅ [COLETOR] {qtd} preços salvos com sucesso.")
                elif qtd == -1:
                    print("⚠️ [COLETOR] Falha ao salvar lote. Dados podem ter sido perdidos (TODO: Retornar ao Redis).")

        except redis.RedisError as e_redis:
            print(f"ERRO REDIS [COLETOR]: {e_redis}")
            time.sleep(5)
            
        except Exception as e_loop:
            print(f"ERRO FATAL NO LOOP [COLETOR]: {e_loop}")
            time.sleep(5)

if __name__ == "__main__":
    main()