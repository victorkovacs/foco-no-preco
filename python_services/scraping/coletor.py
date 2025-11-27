import redis
import json
import time
import mysql.connector

# [CORREÇÃO] Importa do shared
from python_services.shared.conectar_banco import criar_conexao_db, redis_results_pool

NOME_FILA = 'fila_resultados'
TAMANHO_LOTE = 500

def salvar_lote(lista_dados):
    if not lista_dados: return
    
    conn = None
    try:
        conn = criar_conexao_db()
        if not conn: return
        
        cursor = conn.cursor()
        sql = """
        INSERT INTO concorrentes 
        (id_alvo, id_organizacao, id_link_externo, sku, ID_Vendedor, preco, data_extracao)
        VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        
        valores = []
        for item in lista_dados:
            valores.append((
                item.get('id_alvo'), 
                item.get('id_organizacao'), 
                item.get('id_link_externo'),
                item.get('sku'), 
                item.get('ID_Vendedor'), 
                item.get('preco'), 
                item.get('data_extracao')
            ))
            
        cursor.executemany(sql, valores)
        conn.commit()
        print(f"💾 [COLETOR SCRAPE] Salvo lote de {len(valores)} preços.")
        
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"❌ [COLETOR SCRAPE] Erro SQL: {e}")
        if conn: conn.close()

def main():
    print("[COLETOR SCRAPE] Serviço iniciado...")
    try:
        r = redis.Redis(connection_pool=redis_results_pool)
    except Exception as e:
        print(f"Erro Redis: {e}")
        return

    while True:
        try:
            pipeline = r.pipeline()
            pipeline.lrange(NOME_FILA, 0, TAMANHO_LOTE-1)
            pipeline.ltrim(NOME_FILA, TAMANHO_LOTE, -1)
            resultado = pipeline.execute()
            
            itens_json = resultado[0]
            
            if not itens_json:
                time.sleep(1)
                continue
                
            dados_parsed = []
            for j in itens_json:
                try:
                    dados_parsed.append(json.loads(j))
                except:
                    pass
            
            salvar_lote(dados_parsed)
            
        except Exception as e:
            print(f"❌ [COLETOR SCRAPE] Erro no loop: {e}")
            time.sleep(5)

if __name__ == "__main__":
    main()