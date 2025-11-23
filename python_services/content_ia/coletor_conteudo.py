import redis
import json
import time
import sys
import mysql.connector

# --- IMPORTS PADRÃO DOCKER ---
from python_services.shared.conectar_banco import criar_conexao_db
from python_services.content_ia.celery_app_conteudo import redis_conteudo_results_pool
from python_services.content_ia.worker_conteudo import NOME_FILA_RESULTADOS_CONTEUDO

TAMANHO_LOTE = 100
TEMPO_ESPERA = 5

def salvar_lote(lote_resultados):
    if not lote_resultados: return

    conn = None
    try:
        conn = criar_conexao_db()
        if not conn: return
        cursor = conn.cursor()
        
        sucessos = []
        updates_ok = []
        falhas_log = []
        updates_err = []

        for json_str in lote_resultados:
            try:
                d = json.loads(json_str)
                pid = d.get('produto_id')
                
                if d.get('status') == 'sucesso':
                    sucessos.append((
                        pid, d.get('modelo_usado'), 
                        f"template_{d.get('id_template_ia')}", 
                        d.get('json_gerado'), d.get('id_template_ia')
                    ))
                    updates_ok.append(('concluido', pid))
                else:
                    falhas_log.append((pid, 'worker_ia', d.get('mensagem_erro')))
                    updates_err.append(('falhou', pid))
            except:
                pass

        # Persiste Sucessos
        if sucessos:
            sql_ia = """
                INSERT INTO conteudo_gerado_ia 
                (produto_id, modelo_usado, versao_prompt, conteudo_gerado_json, id_template_ia) 
                VALUES (%s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE conteudo_gerado_json = VALUES(conteudo_gerado_json)
            """
            cursor.executemany(sql_ia, sucessos)
            cursor.executemany("UPDATE produtos SET status = %s WHERE id = %s", updates_ok)

        # Persiste Falhas
        if falhas_log:
            cursor.executemany("INSERT INTO logs_tarefas (produto_id, etapa, mensagem_erro) VALUES (%s, %s, %s)", falhas_log)
            cursor.executemany("UPDATE produtos SET status = %s WHERE id = %s", updates_err)

        conn.commit()
        print(f"✅ [COLETOR] Sucessos: {len(sucessos)} | Falhas: {len(falhas_log)}")
        
    except Exception as e:
        print(f"ERRO SQL COLETOR: {e}")
        if conn: conn.rollback()
    finally:
        if conn: conn.close()

def main():
    print("[COLETOR CONTEÚDO] Iniciando (db=4)...")
    r = redis.Redis(connection_pool=redis_conteudo_results_pool)

    while True:
        try:
            p = r.pipeline()
            p.lrange(NOME_FILA_RESULTADOS_CONTEUDO, 0, TAMANHO_LOTE - 1)
            p.ltrim(NOME_FILA_RESULTADOS_CONTEUDO, TAMANHO_LOTE, -1)
            res = p.execute()
            
            lote = res[0]
            if not lote:
                time.sleep(TEMPO_ESPERA)
                continue
                
            salvar_lote(lote)

        except Exception as e:
            print(f"ERRO LOOP COLETOR: {e}")
            time.sleep(10)

if __name__ == "__main__":
    main()