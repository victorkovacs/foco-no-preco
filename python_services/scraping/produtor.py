import time
import sys
import mysql.connector
from datetime import datetime, timedelta

# --- IMPORTS PADRÃO DOCKER ---
from python_services.scraping.celery_app import celery_app
from python_services.scraping.worker import tarefa_scrape
from python_services.shared.conectar_banco import criar_conexao_db

# Configuração
ID_ORGANIZACAO_PADRAO = 1
HORARIO_EXECUCAO = "01:00"

def buscar_alvos_para_fila(conn, id_org):
    cursor = conn.cursor(dictionary=True)
    
    # --- QUERY ATUALIZADA PARA NOVA ESTRUTURA (GLOBAL LINKS) ---
    query = """
    SELECT 
        a.id_alvo, 
        a.id_organizacao,
        p.SKU as sku,
        l.id as id_link_externo,
        
        -- Agora pegamos o link e o vendedor da tabela GLOBAL
        gl.link as link_a_usar,
        v.ID_Vendedor, 
        v.NomeVendedor,
        v.SeletorPreco, 
        v.PercentualDescontoAVista
        
    FROM AlvosMonitoramento a
    JOIN links_externos l ON a.id_link_externo = l.id
    -- JOIN com a tabela Mestre de Links
    JOIN global_links gl ON l.global_link_id = gl.id
    JOIN Produtos p ON a.ID_Produto = p.ID
    -- O Vendedor agora está ligado ao Global Link, não ao link externo local
    JOIN Vendedores v ON gl.ID_Vendedor = v.ID_Vendedor
    
    WHERE a.id_organizacao = %s
      AND a.ativo = 1
      AND (a.status_verificacao IS NULL OR a.status_verificacao != 'Erro_404')
      AND v.SeletorPreco IS NOT NULL 
      AND v.SeletorPreco != ''
      -- Validação agora é no link global
      AND gl.link IS NOT NULL
      AND gl.link != ''
    """
    
    try:
        cursor.execute(query, (id_org,))
        resultados = cursor.fetchall()
        cursor.close()
        return resultados
    except mysql.connector.Error as err:
        print(f"ERRO SQL [PRODUTOR]: {err}")
        return []

def aguardar_ate_horario(horario_alvo_str):
    """Calcula os segundos até o próximo horário alvo e dorme."""
    agora = datetime.now()
    try:
        hora, minuto = map(int, horario_alvo_str.split(':'))
    except ValueError:
        print(f"ERRO: Formato de hora inválido ({horario_alvo_str}). Usando 05:00 padrão.")
        hora, minuto = 5, 0
    
    proxima_execucao = agora.replace(hour=hora, minute=minuto, second=0, microsecond=0)
    
    if agora >= proxima_execucao:
        proxima_execucao += timedelta(days=1)
    
    segundos_espera = (proxima_execucao - agora).total_seconds()
    horas_espera = segundos_espera / 3600
    
    print(f"--- [AGENDADOR] Agora são {agora.strftime('%H:%M:%S')}. Próxima execução agendada para: {proxima_execucao.strftime('%d/%m/%Y %H:%M:%S')} ---")
    print(f"--- [AGENDADOR] O sistema entrará em modo de espera por {horas_espera:.2f} horas... ---")
    
    time.sleep(segundos_espera)

def main():
    print(f"--- [PRODUTOR SCRAPE] Iniciando monitoramento diário (Alvo: {HORARIO_EXECUCAO}) ---")
    
    while True:
        aguardar_ate_horario(HORARIO_EXECUCAO)

        conn = None
        try:
            print(f"--- [PRODUTOR] Acordando! Iniciando ciclo de execução às {datetime.now().strftime('%H:%M:%S')} ---")
            conn = criar_conexao_db()
            if not conn:
                print("ERRO [PRODUTOR]: Falha ao conectar no DB. Tentando novamente em 60s...")
                time.sleep(60)
                continue

            print("--- [PRODUTOR] Buscando alvos no banco... ---")
            alvos = buscar_alvos_para_fila(conn, ID_ORGANIZACAO_PADRAO)
            
            if not alvos:
                print("--- [PRODUTOR] Nenhum alvo ativo encontrado para hoje. ---")
            else:
                print(f"--- [PRODUTOR] Encontrados {len(alvos)} alvos. Enviando para fila de processamento... ---")
                
                enviados = 0
                for alvo in alvos:
                    if alvo.get('PercentualDescontoAVista'):
                        alvo['PercentualDescontoAVista'] = float(alvo['PercentualDescontoAVista'])
                    
                    tarefa_scrape.delay(alvo)
                    enviados += 1
                
                print(f"✅ [PRODUTOR] {enviados} tarefas enviadas com sucesso para a fila.")

            conn.close()
            print("--- [PRODUTOR] Ciclo finalizado. Voltando a dormir até o próximo agendamento. ---")

        except Exception as e:
            print(f"ERRO FATAL [PRODUTOR]: {e}")
            time.sleep(60)
            if conn and conn.is_connected():
                conn.close()

if __name__ == "__main__":
    main()