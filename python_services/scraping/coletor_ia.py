import redis
import json
import time
import sys
import mysql.connector

from python_services.scraping.celery_app import redis_ia_results_pool
from python_services.shared.conectar_banco import criar_conexao_db

TAMANHO_LOTE = 100
NOME_FILA = 'fila_ia_resultados' # Redis db=2

def salvar_matches(lista):
    if not lista: return
    conn = None
    try:
        conn = criar_conexao_db()
        if not conn: return
        cursor = conn.cursor()

        for m in lista:
            # 1. Cria vinculo em AlvosMonitoramento
            sql1 = """
            INSERT INTO AlvosMonitoramento (id_organizacao, ID_Produto, id_link_externo, ativo, data_criacao)
            VALUES (%s, %s, %s, 1, NOW())
            ON DUPLICATE KEY UPDATE ativo = 1
            """
            cursor.execute(sql1, (m['id_organizacao'], m['id_produto'], m['id_link_externo']))
            
            # 2. Atualiza Link Externo
            cursor.execute("UPDATE links_externos SET status = 'match_ia' WHERE id = %s", (m['id_link_externo'],))
        
        conn.commit()
        print(f"✅ [COLETOR IA] {len(lista)} matches salvos.")
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"ERRO SQL COLETOR IA: {e}")
        if conn: conn.close()

def main():
    print("[COLETOR IA] Iniciando...")
    try:
        r = redis.Redis(connection_pool=redis_ia_results_pool)
    except:
        return

    while True:
        try:
            p = r.pipeline()
            p.lrange(NOME_FILA, 0, TAMANHO_LOTE-1)
            p.ltrim(NOME_FILA, TAMANHO_LOTE, -1)
            res = p.execute()
            
            lista_json = res[0]
            if not lista_json:
                time.sleep(2)
                continue
                
            lista = [json.loads(x) for x in lista_json]
            salvar_matches(lista)
            
        except Exception as e:
            print(f"ERRO LOOP COLETOR IA: {e}")
            time.sleep(10)

if __name__ == "__main__":
    main()