import os
import io
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.http import MediaIoBaseDownload

# --- CONFIGURAÇÕES (DEVEM SER IGUAIS AO BACKUP) ---
FOLDER_ID = '1JtSFMzG189AaxkgTataV-6H2w7abl3hK' 
SERVICE_ACCOUNT_FILE = '/app/service_account.json'
SCOPES = ['https://www.googleapis.com/auth/drive']
DESTINO = '/app/restore_temp.sql' # Salva na raiz (volume mapeado)

def baixar_ultimo_backup():
    if not os.path.exists(SERVICE_ACCOUNT_FILE):
        print("[RESTORE] ❌ Arquivo service_account.json não encontrado.")
        return False

    try:
        creds = service_account.Credentials.from_service_account_file(
            SERVICE_ACCOUNT_FILE, scopes=SCOPES
        )
        service = build('drive', 'v3', credentials=creds)

        # 1. Busca o arquivo mais recente
        print("[RESTORE] Buscando backup mais recente no Google Drive...")
        results = service.files().list(
            q=f"'{FOLDER_ID}' in parents and trashed=false",
            orderBy='createdTime desc',
            pageSize=1,
            fields="files(id, name)"
        ).execute()
        
        items = results.get('files', [])

        if not items:
            print("[RESTORE] ⚠️ Nenhum backup encontrado no Drive.")
            return False

        arquivo = items[0]
        print(f"[RESTORE] Encontrado: {arquivo['name']} (ID: {arquivo['id']})")

        # 2. Faz o Download
        request = service.files().get_media(fileId=arquivo['id'])
        fh = io.FileIO(DESTINO, 'wb')
        downloader = MediaIoBaseDownload(fh, request)
        
        done = False
        while done is False:
            status, done = downloader.next_chunk()
            
        print(f"[RESTORE] ✅ Download concluído: {DESTINO}")
        return True

    except Exception as e:
        print(f"[RESTORE] ❌ Erro ao baixar backup: {e}")
        return False

if __name__ == '__main__':
    sucesso = baixar_ultimo_backup()
    exit(0 if sucesso else 1)