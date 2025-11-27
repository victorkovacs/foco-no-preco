import redis
import json
import time
import sys
import mysql.connector

# [CORREÇÃO] Importa do shared, não do celery_app
from python_services.shared.conectar_banco import criar_conexao_db, redis_ia_results_pool

NOME_FILA = 'fila_ia_resultados'
TAMANHO_LOTE = 100

def salvar_match(dados):
    if not dados: return
    
    conn = None
    try:
        conn = criar_conexao_db()
        if not conn: return
        
        cursor = conn.cursor()
        
        # Query para salvar o link validado (match)
        # Ajuste conforme sua tabela real. Supondo que seja para inserir em 'links_externos' ou atualizar
        # Vou manter uma lógica genérica baseada no seu worker anterior
        
        # Exemplo: Atualiza o produto com o link encontrado ou insere na tabela de concorrentes
        # Como o worker retorna "id_link_externo" (vencedor), vamos supor update ou insert.
        
        # Lógica: Se houve match, atualiza/insere
        sql = """
        INSERT INTO concorrentes (id_alvo, id_organizacao, id_link_externo, data_match, origem)
        VALUES (%s, %s, %s, %s, 'IA')
        ON DUPLICATE KEY UPDATE data_match = VALUES(data_match)
        """
        
        valores = []
        for item in dados:
            # Proteção contra JSON incompleto
            if 'id_produto' in item and 'id_link_externo' in item:
                valores.append((
                    item['id_produto'], 
                    item.get('id_organizacao'), 
                    item['id_link_externo'], 
                    item.get('match_data')
                ))
        
        if valores:
            cursor.executemany(sql, valores)
            conn.commit()
            print(f"💾 [COLETOR IA] Salvo {len(valores)} matches no MySQL.")
        
        cursor.close()
        conn.close()

    except Exception as e:
        print(f"❌ [COLETOR IA] Erro SQL: {e}")
        if conn: conn.close()

def main():
    print("[COLETOR IA] Serviço iniciado. Aguardando resultados...")
    try:
        r = redis.Redis(connection_pool=redis_ia_results_pool)
    except Exception as e:
        print(f"Erro conexão Redis: {e}")
        return

    while True:
        try:
            pipeline = r.pipeline()
            pipeline.lrange(NOME_FILA, 0, TAMANHO_LOTE-1)
            pipeline.ltrim(NOME_FILA, TAMANHO_LOTE, -1)
            resultado = pipeline.execute()
            
            itens_json = resultado[0]
            
            if not itens_json:
                time.sleep(5) 
                continue
                
            dados_parsed = []
            for j in itens_json:
                try:
                    dados_parsed.append(json.loads(j))
                except:
                    pass
            
            salvar_match(dados_parsed)
            
        except Exception as e:
            print(f"❌ [COLETOR IA] Erro no loop: {e}")
            time.sleep(5)

if __name__ == "__main__":
    main()