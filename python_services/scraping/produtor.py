import time
import sys
import mysql.connector

# --- AJUSTE DE IMPORTAÇÃO (Padrão Docker) ---
from python_services.scraping.celery_app import celery_app
from python_services.scraping.worker import tarefa_scrape
from python_services.shared.conectar_banco import criar_conexao_db

# Configuração
ID_ORGANIZACAO_PADRAO = 1
INTERVALO_ENTRE_CICLOS = 600 # 10 minutos (ajuste conforme necessidade)

def buscar_alvos_para_fila(conn, id_org):
    """
    Busca alvos ativos no banco de dados para a organização especificada.
    Recupera: Link, Seletores, Vendedor, Produto e Descontos.
    """
    cursor = conn.cursor(dictionary=True)
    
    # Query SQL completa baseada no seu arquivo original
    query = """
    SELECT 
        a.id_alvo, 
        a.id_organizacao,
        p.SKU as sku,
        l.id as id_link_externo,
        l.url as link_a_usar,
        v.ID_Vendedor, 
        v.NomeVendedor,
        v.SeletorPreco, 
        v.PercentualDescontoAVista
        
    FROM AlvosMonitoramento a
    JOIN links_externos l ON a.id_link_externo = l.id
    JOIN produtos p ON a.ID_Produto = p.id
    JOIN Vendedores v ON l.id_vendedor = v.ID_Vendedor
    
    WHERE a.id_organizacao = %s
      AND a.ativo = 1
      AND (a.status_verificacao IS NULL OR a.status_verificacao != 'Erro_404')
      AND v.SeletorPreco IS NOT NULL 
      AND v.SeletorPreco != ''
      AND l.url IS NOT NULL
      AND l.url != ''
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
    print(f"--- [PRODUTOR] Iniciando monitoramento para Org ID {ID_ORGANIZACAO_PADRAO} ---")
    
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
                    # Garante conversão de tipos (Decimal -> Float)
                    if alvo.get('PercentualDescontoAVista'):
                        alvo['PercentualDescontoAVista'] = float(alvo['PercentualDescontoAVista'])
                    
                    # Envia para o Celery (Worker)
                    # O método .delay() serializa o dicionário 'alvo' como JSON automaticamente
                    tarefa_scrape.delay(alvo)
                    enviados += 1
                
                print(f"✅ [PRODUTOR] {enviados} tarefas enviadas com sucesso.")

            # Fecha conexão para não estourar o limite do banco enquanto dorme
            conn.close()
            
            # Aguarda o próximo ciclo
            print(f"--- [PRODUTOR] Aguardando {INTERVALO_ENTRE_CICLOS} segundos... ---")
            time.sleep(INTERVALO_ENTRE_CICLOS)

        except Exception as e:
            print(f"ERRO FATAL [PRODUTOR]: {e}")
            time.sleep(60)
            if conn and conn.is_connected():
                conn.close()

if __name__ == "__main__":
    main()