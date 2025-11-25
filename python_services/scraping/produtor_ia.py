import time
import sys
import mysql.connector

from python_services.scraping.worker_ia import tarefa_match_ia
from python_services.shared.conectar_banco import criar_conexao_db

ID_ORG = 1

def buscar_candidatos(conn, palavras):
    if not palavras: return []
    # CORREÇÃO: l.url -> l.link na busca LIKE
    filtro = " AND ".join([f"l.link LIKE '%{p}%'" for p in palavras[:3]])
    
    # CORREÇÃO: l.titulo -> l.nome e l.url -> l.link
    # Usamos alias (AS) para não quebrar o worker que espera 'titulo' e 'url'
    sql = f"""
    SELECT 
        l.id, 
        l.nome as titulo,   -- <--- O banco tem 'nome', o worker quer 'titulo'
        l.link as url,      -- <--- O banco tem 'link', o worker quer 'url'
        v.NomeVendedor
    FROM links_externos l
    JOIN Vendedores v ON l.id_vendedor = v.ID_Vendedor
    LEFT JOIN AlvosMonitoramento a ON l.id = a.id_link_externo
    WHERE l.id_organizacao = %s AND a.id_alvo IS NULL AND {filtro}
    LIMIT 20
    """
    c = conn.cursor(dictionary=True)
    try:
        c.execute(sql, (ID_ORG,))
        return c.fetchall()
    except Exception as e:
        print(f"Erro ao buscar candidatos: {e}")
        return []

def main():
    print("[PRODUTOR MATCH IA] Iniciando...")
    while True:
        try:
            conn = criar_conexao_db()
            if not conn:
                time.sleep(30)
                continue

            c = conn.cursor(dictionary=True)
            c.execute("SELECT ID, Nome, SKU FROM Produtos WHERE id_organizacao = %s AND ia_processado = 0 LIMIT 5", (ID_ORG,))
            produtos = c.fetchall()
            c.close()

            if not produtos:
                print("[PRODUTOR IA] Nada pendente. Dormindo...")
                conn.close()
                time.sleep(60)
                continue

            for p in produtos:
                palavras = [w for w in p['Nome'].split() if len(w) > 3]
                candidatos = buscar_candidatos(conn, palavras)
                
                if candidatos:
                    payload = {
                        "id_produto": p['ID'],
                        "produto_sku": p['SKU'],
                        "nome_produto": p['Nome'],
                        "candidatos": candidatos,
                        "id_organizacao": ID_ORG
                    }
                    tarefa_match_ia.delay(payload)
                    print(f"-> Tarefa IA enviada: {p['SKU']}")

                c2 = conn.cursor()
                c2.execute("UPDATE Produtos SET ia_processado = 1 WHERE ID = %s", (p['ID'],))
                conn.commit()
                c2.close()

            conn.close()
            time.sleep(5)

        except Exception as e:
            print(f"ERRO PRODUTOR IA: {e}")
            time.sleep(30)

if __name__ == "__main__":
    main()