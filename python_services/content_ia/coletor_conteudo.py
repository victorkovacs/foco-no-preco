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
        
        sucessos_conteudo = [] # Para salvar o JSON final (conteudo_gerado_ia)
        updates_fila_ok = []   # IDs da fila para marcar como 'concluido'
        updates_fila_err = []  # IDs da fila para marcar como 'erro'

        for json_str in lote_resultados:
            try:
                d = json.loads(json_str)
                
                # Dados fundamentais
                pid = d.get('id') or d.get('produto_id') # ID numérico do produto (para vincular o conteúdo)
                id_fila = d.get('id_tarefa_fila')        # ID da tarefa na Fila (para dar baixa)
                
                if d.get('status') == 'sucesso':
                    # 1. Prepara insert na tabela de RESULTADO FINAL (conteudo_gerado_ia)
                    # Note que salvamos vinculado ao ID do produto, pois o conteúdo pertence ao produto.
                    sucessos_conteudo.append((
                        pid, 
                        d.get('modelo_usado'), 
                        f"template_{d.get('id_template_ia')}", 
                        d.get('json_gerado'), 
                        d.get('id_template_ia')
                    ))
                    
                    # 2. Marca a tarefa da FILA como concluida
                    if id_fila:
                        updates_fila_ok.append(id_fila)
                else:
                    # Falha
                    msg_erro = d.get('mensagem_erro', 'Erro desconhecido')
                    if id_fila:
                        updates_fila_err.append((msg_erro, id_fila))

            except Exception as e:
                print(f"⚠️ Erro ao processar item do Redis: {e}")

        # --- A. Salva o Conteúdo Gerado (Tabela de Resultados) ---
        if sucessos_conteudo:
            sql_ia = """
                INSERT INTO conteudo_gerado_ia 
                (produto_id, modelo_usado, versao_prompt, conteudo_gerado_json, id_template_ia) 
                VALUES (%s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE conteudo_gerado_json = VALUES(conteudo_gerado_json)
            """
            cursor.executemany(sql_ia, sucessos_conteudo)

        # --- B. Atualiza a FILA (Status) - ISOLADO DE PRODUTOS ---
        if updates_fila_ok:
            # Atualiza apenas a FilaGeracaoConteudo
            format_strings = ','.join(['%s'] * len(updates_fila_ok))
            sql_ok = f"UPDATE FilaGeracaoConteudo SET status = 'concluido', updated_at = NOW() WHERE id IN ({format_strings})"
            cursor.execute(sql_ok, tuple(updates_fila_ok))

        if updates_fila_err:
            sql_err = "UPDATE FilaGeracaoConteudo SET status = 'erro', mensagem_erro = %s, updated_at = NOW() WHERE id = %s"
            cursor.executemany(sql_err, updates_fila_err)

        conn.commit()
        print(f"✅ [COLETOR] Lote processado. Sucessos: {len(sucessos_conteudo)} | Erros: {len(updates_fila_err)}")
        
    except Exception as e:
        print(f"❌ ERRO SQL COLETOR: {e}")
        if conn: conn.rollback()
    finally:
        if conn: conn.close()

def main():
    print("[COLETOR CONTEÚDO] Iniciando (Modo Isolado)...")
    try:
        r = redis.Redis(connection_pool=redis_conteudo_results_pool)
    except Exception as e:
        print(f"Erro conexão Redis: {e}")
        return

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