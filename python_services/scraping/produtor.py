import time
import sys
import mysql.connector

# --- IMPORTS PADRÃO DOCKER ---
from python_services.scraping.celery_app import celery_app
from python_services.scraping.worker import tarefa_scrape
from python_services.shared.conectar_banco import criar_conexao_db

# Configuração
ID_ORGANIZACAO_PADRAO = 1
INTERVALO_ENTRE_CICLOS = 600 # 10 minutos

def buscar_alvos_para_fila(conn, id_org):
    cursor = conn.cursor(dictionary=True)
    
    # CORREÇÃO: l.url -> l.link
    query = """
    SELECT 
        a.id_alvo, 
        a.id_organizacao,
        p.SKU as sku,
        l.id as id_link_externo,
        l.link as link_a_usar,   -- <--- CORRIGIDO AQUI (Era l.url)
        v.ID_Vendedor, 
        v.NomeVendedor,
        v.SeletorPreco, 
        v.PercentualDescontoAVista
        
    FROM AlvosMonitoramento a
    JOIN links_externos l ON a.id_link_externo = l.id
    JOIN Produtos p ON a.ID_Produto = p.ID
    JOIN Vendedores v ON l.id_vendedor = v.ID_Vendedor
    
    WHERE a.id_organizacao = %s
      AND a.ativo = 1
      AND (a.status_verificacao IS NULL OR a.status_verificacao != 'Erro_404')
      AND v.SeletorPreco IS NOT NULL 
      AND v.SeletorPreco != ''
      AND l.link IS NOT NULL    -- <--- CORRIGIDO AQUI
      AND l.link != ''          -- <--- CORRIGIDO AQUI
    """
    
    try:
        cursor.execute(query, (id_org,))
        resultados = cursor.fetchall()
        cursor.close()
        return resultados
    except mysql.connector.Error as err:
        print(f"ERRO SQL [PRODUTOR]: {err}")
        return []

def main():
    print(f"--- [PRODUTOR SCRAPE] Iniciando monitoramento para Org ID {ID_ORGANIZACAO_PADRAO} ---")
    
    while True:
        conn = None
        try:
            conn = criar_conexao_db()
            if not conn:
                print("ERRO [PRODUTOR]: Falha ao conectar no DB. Tentando em 30s...")
                time.sleep(30)
                continue

            # 1. Busca Alvos
            print("--- [PRODUTOR] Buscando alvos no banco... ---")
            alvos = buscar_alvos_para_fila(conn, ID_ORGANIZACAO_PADRAO)
            
            if not alvos:
                print("--- [PRODUTOR] Nenhum alvo pendente encontrado. ---")
            else:
                print(f"--- [PRODUTOR] Encontrados {len(alvos)} alvos. Enviando para fila... ---")
                
                enviados = 0
                for alvo in alvos:
                    if alvo.get('PercentualDescontoAVista'):
                        alvo['PercentualDescontoAVista'] = float(alvo['PercentualDescontoAVista'])
                    
                    tarefa_scrape.delay(alvo)
                    enviados += 1
                
                print(f"✅ [PRODUTOR] {enviados} tarefas enviadas com sucesso.")

            conn.close()
            print(f"--- [PRODUTOR] Aguardando {INTERVALO_ENTRE_CICLOS} segundos... ---")
            time.sleep(INTERVALO_ENTRE_CICLOS)

        except Exception as e:
            print(f"ERRO FATAL [PRODUTOR]: {e}")
            time.sleep(60)
            if conn and conn.is_connected():
                conn.close()

if __name__ == "__main__":
    main()