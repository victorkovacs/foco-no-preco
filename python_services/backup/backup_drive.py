import os
import time
import datetime
import subprocess
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload

# --- CONFIGURAÇÕES ---

# ID DA PASTA NO GOOGLE DRIVE (Extraído do seu link)
FOLDER_ID = '1JtSFMzG189AaxkgTataV-6H2w7abl3hK' 

# Caminho para o arquivo de credenciais (Dentro do Docker)
SERVICE_ACCOUNT_FILE = '/app/service_account.json'

# Dados do Banco (Lidos das variáveis de ambiente do Docker)
DB_HOST = os.getenv('DB_HOST', 'db')
DB_USER = os.getenv('DB_USERNAME', 'root')
DB_PASS = os.getenv('DB_PASSWORD', '12345678')
DB_NAME = os.getenv('DB_DATABASE', 'foconopreco')

SCOPES = ['https://www.googleapis.com/auth/drive']

def criar_dump_sql():
    """
    Gera um arquivo .sql com o backup do banco de dados localmente.
    """
    data_hoje = datetime.datetime.now().strftime("%Y-%m-%d_%H-%M")
    nome_arquivo = f"backup_{DB_NAME}_{data_hoje}.sql"
    caminho_completo = f"/tmp/{nome_arquivo}"

    print(f"[BACKUP] Criando dump do banco '{DB_NAME}'...")
    
    # Comando mysqldump (ferramenta oficial do MySQL)
    # Importante: Não deixa espaço entre -p e a senha
    cmd = f"mysqldump -h {DB_HOST} -u {DB_USER} -p{DB_PASS} {DB_NAME} > {caminho_completo}"
    
    # Executa o comando no sistema Linux do container
    retorno = os.system(cmd)
    
    if retorno == 0:
        print(f"[BACKUP] Dump criado com sucesso: {caminho_completo}")
        return caminho_completo, nome_arquivo
    else:
        print("[BACKUP] ERRO ao criar dump do banco. Verifique as credenciais.")
        return None, None

def upload_para_drive(caminho_arquivo, nome_arquivo):
    """
    Envia o arquivo gerado para a pasta do Google Drive.
    """
    if not os.path.exists(SERVICE_ACCOUNT_FILE):
        print(f"[BACKUP] Erro Crítico: Arquivo de credenciais não encontrado em {SERVICE_ACCOUNT_FILE}")
        print("Verifique se você renomeou para 'service_account.json' e colocou na raiz.")
        return

    print("[BACKUP] Autenticando no Google Drive...")
    try:
        creds = service_account.Credentials.from_service_account_file(
            SERVICE_ACCOUNT_FILE, scopes=SCOPES
        )
        service = build('drive', 'v3', credentials=creds)

        file_metadata = {
            'name': nome_arquivo,
            'parents': [FOLDER_ID]
        }
        media = MediaFileUpload(caminho_arquivo, mimetype='application/sql')

        print(f"[BACKUP] Iniciando upload de {nome_arquivo}...")
        file = service.files().create(
            body=file_metadata,
            media_body=media,
            fields='id'
        ).execute()

        print(f"[BACKUP] ✅ Sucesso! Backup enviado. ID do Arquivo: {file.get('id')}")
        
    except Exception as e:
        print(f"[BACKUP] ❌ Falha na conexão com o Google Drive: {e}")

def main():
    print("--- INICIANDO ROTINA DE BACKUP AUTOMÁTICO ---")
    
    # 1. Cria o arquivo SQL localmente
    caminho, nome = criar_dump_sql()
    
    if caminho:
        try:
            # 2. Sobe para o Drive
            upload_para_drive(caminho, nome)
        except Exception as e:
            print(f"[BACKUP] Erro inesperado no processo: {e}")
        finally:
            # 3. Limpa o arquivo local para não encher o disco do container
            if os.path.exists(caminho):
                os.remove(caminho)
                print("[BACKUP] Arquivo temporário removido do servidor.")

if __name__ == '__main__':
    # Loop infinito para rodar a cada 24 horas (86400 segundos)
    print("[SISTEMA] Robô de Backup iniciado.")
    while True:
        main()
        print("[SISTEMA] Dormindo por 24 horas até o próximo backup...")
        time.sleep(86400)