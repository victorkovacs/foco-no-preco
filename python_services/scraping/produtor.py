import time
import sys
import mysql.connector
from datetime import datetime, timedelta

# --- IMPORTS PADRÃO DO SEU PROJETO ---
# Certifique-se que o Dockerfile está configurado para achar esses caminhos
from python_services.scraping.celery_app import celery_app
from python_services.scraping.worker import tarefa_scrape
from python_services.shared.conectar_banco import criar_conexao_db

# Configuração Padrão (caso o banco falhe na leitura)
ID_ORGANIZACAO_PADRAO = 1
HORARIO_PADRAO_SCRAPE = '01:00'

def buscar_horario_configurado(conn):
    """
    Busca o horário de scraping na tabela de configurações.
    Retorna o valor do banco ou o padrão '01:00'.
    """
    try:
        cursor = conn.cursor(dictionary=True)
        # Busca pela chave que definimos na Migration do Laravel
        cursor.execute("SELECT valor FROM configuracoes_sistema WHERE chave = 'horario_scraping'")
        res = cursor.fetchone()
        cursor.close()
        
        if res and res['valor']:
            return res['valor']
        return HORARIO_PADRAO_SCRAPE
        
    except Exception as e:
        print(f"⚠️ [PRODUTOR] Erro ao ler configuração do banco: {e}. Usando padrão {HORARIO_PADRAO_SCRAPE}.")
        return HORARIO_PADRAO_SCRAPE

def aguardar_ate_horario_dinamico(conn):
    """
    Lê o horário do banco, calcula exatamente quantos segundos faltam 
    e dorme profundamente até lá.
    """
    # 1. Lê a configuração atualizada do banco
    horario_alvo_str = buscar_horario_configurado(conn)
    
    agora = datetime.now()
    
    # Tratamento simples para garantir formato HH:MM
    try:
        hora, minuto = map(int, horario_alvo_str.split(':'))
    except ValueError:
        print(f"❌ [PRODUTOR] Formato de hora inválido no banco ({horario_alvo_str}). Resetando para 01:00.")
        hora, minuto = 1, 0
    
    # 2. Define a data/hora da próxima execução
    proxima_execucao = agora.replace(hour=hora, minute=minuto, second=0, microsecond=0)
    
    # Se o horário alvo já passou hoje (ex: agora é 14:00 e o alvo é 01:00), agenda para amanhã
    if agora >= proxima_execucao:
        proxima_execucao += timedelta(days=1)
    
    # 3. Calcula o tempo de sono
    segundos_espera = (proxima_execucao - agora).total_seconds()
    horas_espera = segundos_espera / 3600
    
    print(f"--- [AGENDADOR SCRAPE] Leitura do DB: Horário alvo é {horario_alvo_str} ---")
    print(f"--- [AGENDADOR SCRAPE] Dormindo por {horas_espera:.2f} horas (até {proxima_execucao.strftime('%d/%m %H:%M:%S')}) ---")
    
    # 4. Dorme direto (sem acordar a cada minuto)
    time.sleep(segundos_espera)
    return True

def buscar_alvos_para_fila(conn, id_org):
    """
    Busca os produtos ativos e seus links para enviar à fila.
    """
    cursor = conn.cursor(dictionary=True)
    
    # Query ajustada conforme sua estrutura de tabelas (Global Links)
    query = """
    SELECT 
        a.id_alvo, 
        a.id_organizacao,
        p.SKU as sku,
        l.id as id_link_externo,
        gl.link as link_a_usar,
        v.ID_Vendedor, 
        v.NomeVendedor,
        v.SeletorPreco, 
        v.PercentualDescontoAVista
    FROM AlvosMonitoramento a
    JOIN links_externos l ON a.id_link_externo = l.id
    JOIN global_links gl ON l.global_link_id = gl.id
    JOIN Produtos p ON a.ID_Produto = p.ID
    JOIN Vendedores v ON gl.ID_Vendedor = v.ID_Vendedor
    WHERE a.id_organizacao = %s
      AND a.ativo = 1
      AND (a.status_verificacao IS NULL OR a.status_verificacao != 'Erro_404')
      AND v.SeletorPreco IS NOT NULL 
      AND v.SeletorPreco != ''
      AND gl.link IS NOT NULL
      AND gl.link != ''
    """
    
    try:
        cursor.execute(query, (id_org,))
        resultados = cursor.fetchall()
        cursor.close()
        return resultados
    except mysql.connector.Error as err:
        print(f"❌ [PRODUTOR] Erro SQL: {err}")
        return []

def main():
    print(f"--- [PRODUTOR SCRAPE] Serviço Iniciado (Modo: Sleep Otimizado) ---")
    
    # Loop Infinito do Processo
    while True:
        conn = None
        try:
            # Conecta para checar horário e depois fecha (para não segurar conexão aberta durante o sono)
            conn = criar_conexao_db()
            if not conn:
                print("⚠️ [PRODUTOR] Sem conexão com banco. Tentando em 60s...")
                time.sleep(60)
                continue

            # --- PASSO 1: DORMIR ATÉ O HORÁRIO ---
            # O script vai travar nesta linha por horas, sem gastar CPU
            aguardar_ate_horario_dinamico(conn)
            
            # --- PASSO 2: ACORDOU! HORA DE TRABALHAR ---
            print(f"⏰ [PRODUTOR] Acordando! Iniciando ciclo às {datetime.now().strftime('%H:%M:%S')} ---")
            
            # Como a conexão pode ter caído durante o sono longo, verificamos ou reconectamos
            if not conn.is_connected():
                conn.close()
                conn = criar_conexao_db()

            alvos = buscar_alvos_para_fila(conn, ID_ORGANIZACAO_PADRAO)
            
            if alvos:
                print(f"--- [PRODUTOR] Processando {len(alvos)} alvos... ---")
                enviados = 0
                for alvo in alvos:
                    # Converte Decimal para float para serializar no JSON do Celery
                    if alvo.get('PercentualDescontoAVista'):
                        alvo['PercentualDescontoAVista'] = float(alvo['PercentualDescontoAVista'])
                    
                    # Envia para o RabbitMQ/Redis
                    tarefa_scrape.delay(alvo)
                    enviados += 1
                
                print(f"✅ [PRODUTOR] Sucesso! {enviados} tarefas enviadas para a fila.")
            else:
                print("--- [PRODUTOR] Nenhum alvo ativo encontrado hoje. ---")

            # Fecha conexão e reinicia o loop para calcular o próximo sono
            conn.close()
            print("--- [PRODUTOR] Ciclo finalizado. Preparando próximo agendamento... ---")
            
            # Pausa de segurança de 5s para garantir que saímos do segundo exato da execução
            time.sleep(5) 

        except Exception as e:
            print(f"❌ [PRODUTOR] Erro Fatal no Loop: {e}")
            # Em caso de erro, espera 1 minuto e tenta reiniciar o ciclo
            time.sleep(60)
            if conn and conn.is_connected():
                conn.close()

if __name__ == "__main__":
    main()