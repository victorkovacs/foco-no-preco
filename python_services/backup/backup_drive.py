import os
import time
import datetime
import sys
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload

# --- CONFIGURAÇÕES ---
FOLDER_ID = '1JtSFMzG189AaxkgTataV-6H2w7abl3hK' 
NOME_ARQUIVO_FIXO = 'backup_foconopreco_db.sql'
SERVICE_ACCOUNT_FILE = '/app/service_account.json'

# Dados do Banco
DB_HOST = os.getenv('DB_HOST', 'db')
DB_USER = os.getenv('DB_USERNAME', 'root')
DB_PASS = os.getenv('DB_PASSWORD', 'secret123')
DB_NAME = os.getenv('DB_DATABASE', 'foconopreco')

SCOPES = ['https://www.googleapis.com/auth/drive']

def criar_dump_sql():
    # Cria arquivo local temporário
    caminho_completo = f"/tmp/{NOME_ARQUIVO_FIXO}"
    print(f"[BACKUP] Gerando dump SQL em {caminho_completo}...")
    
    # --skip-ssl para evitar erro de certificado no Docker
    cmd = f"mysqldump --skip-ssl -h {DB_HOST} -u {DB_USER} -p{DB_PASS} {DB_NAME} > {caminho_completo}"
    
    retorno = os.system(cmd)
    if retorno == 0:
        return caminho_completo
    else:
        print(f"[BACKUP] ❌ Erro (Cód {retorno}) ao gerar dump MySQL.")
        return None

def buscar_arquivo_existente(service):
    """Procura o ID do arquivo que vamos sobrescrever"""
    query = f"name = '{NOME_ARQUIVO_FIXO}' and '{FOLDER_ID}' in parents and trashed = false"
    results = service.files().list(q=query, fields="files(id, name)").execute()
    items = results.get('files', [])
    
    if not items:
        return None
    return items[0]['id']

def atualizar_drive(caminho_local):
    if not os.path.exists(SERVICE_ACCOUNT_FILE):
        print("[BACKUP] ❌ Arquivo service_account.json não encontrado.")
        return

    try:
        creds = service_account.Credentials.from_service_account_file(
            SERVICE_ACCOUNT_FILE, scopes=SCOPES
        )
        service = build('drive', 'v3', credentials=creds)

        # 1. Procura o arquivo placeholder
        file_id = buscar_arquivo_existente(service)
        
        if not file_id:
            print(f"[BACKUP] ⚠️ ARQUIVO NÃO ENCONTRADO NO DRIVE!")
            print(f"   Por favor, crie um arquivo vazio chamado '{NOME_ARQUIVO_FIXO}' dentro da pasta do ID '{FOLDER_ID}'.")
            print("   O robô precisa atualizar um arquivo existente para usar sua cota de armazenamento.")
            return

        # 2. Atualiza o conteúdo (Cria nova versão no histórico)
        print(f"[BACKUP] Atualizando arquivo (ID: {file_id})...")
        
        media = MediaFileUpload(caminho_local, mimetype='application/sql', resumable=True)
        
        # Usa método update em vez de create
        arquivo_atualizado = service.files().update(
            fileId=file_id,
            media_body=media,
            fields='id, version'
        ).execute()

        print(f"[BACKUP] ✅ SUCESSO! Backup atualizado. Versão Drive: {arquivo_atualizado.get('version')}")

    except Exception as e:
        print(f"[BACKUP] ❌ Erro na API do Drive: {e}")

def main():
    print("--- INICIANDO BACKUP (MÉTODO ATUALIZAÇÃO) ---")
    arquivo_sql = criar_dump_sql()
    
    if arquivo_sql:
        atualizar_drive(arquivo_sql)
        if os.path.exists(arquivo_sql):
            os.remove(arquivo_sql)

if __name__ == '__main__':
    print("[SISTEMA] Robô de Backup iniciado.")
    while True:
        main()
        print("[SISTEMA] Aguardando 24h...")
        time.sleep(86400)