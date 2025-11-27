import os
import time
import datetime
import subprocess
import sys
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload

# Importa função de segurança do Shared (se disponível) ou define fallback
try:
    from python_services.shared.conectar_banco import get_docker_secret
except ImportError:
    def get_docker_secret(name, default=None):
        try:
            with open(f'/run/secrets/{name}', 'r') as f:
                return f.read().strip()
        except:
            return os.getenv(name.upper(), default)

# --- CONFIGURAÇÕES ---
FOLDER_ID = '1JtSFMzG189AaxkgTataV-6H2w7abl3hK' 
NOME_ARQUIVO_FIXO = 'backup_foconopreco_db.sql'
SERVICE_ACCOUNT_FILE = '/app/service_account.json'

# --- DADOS DO BANCO (LENDO DE SECRETS) ---
DB_HOST = os.getenv('DB_HOST', 'db')
DB_USER = get_docker_secret('db_user', os.getenv('DB_USERNAME', 'root'))
DB_NAME = os.getenv('DB_DATABASE', 'foconopreco')

# Tenta ler do secret primeiro, depois do env
DB_PASS = get_docker_secret('db_password', os.getenv('DB_PASSWORD'))
# Fallback para root password se necessário
if not DB_PASS:
    DB_PASS = get_docker_secret('db_root_password')

SCOPES = ['https://www.googleapis.com/auth/drive']

def criar_dump_sql():
    if not DB_PASS:
        print("[BACKUP] ❌ Erro Crítico: Senha do banco não encontrada (Secrets/Env vazios).")
        return None

    caminho_completo = f"/tmp/{NOME_ARQUIVO_FIXO}"
    print(f"[BACKUP] Iniciando dump do banco {DB_NAME} em {DB_HOST}...")
    
    # Configura ambiente seguro para passar a senha (evita expor no comando ps)
    env_dump = os.environ.copy()
    env_dump['MYSQL_PWD'] = DB_PASS

    # Comando seguro sem a senha explícita na string
    cmd = [
        'mysqldump',
        '--skip-ssl',
        '-h', DB_HOST,
        '-u', DB_USER,
        DB_NAME
    ]

    try:
        with open(caminho_completo, 'w') as output_file:
            subprocess.run(cmd, env=env_dump, stdout=output_file, check=True)
        
        # Verifica se o arquivo não está vazio
        if os.path.getsize(caminho_completo) > 0:
            print(f"[BACKUP] Dump gerado com sucesso: {caminho_completo}")
            return caminho_completo
        else:
            print("[BACKUP] ❌ Erro: O arquivo de dump foi criado vazio.")
            return None

    except subprocess.CalledProcessError as e:
        print(f"[BACKUP] ❌ Falha no mysqldump (Código {e.returncode}). Verifique se 'default-mysql-client' está instalado na imagem.")
        return None
    except Exception as e:
        print(f"[BACKUP] ❌ Erro inesperado ao gerar dump: {e}")
        return None

def buscar_arquivo_existente(service):
    """Procura o ID do arquivo que vamos sobrescrever"""
    try:
        query = f"name = '{NOME_ARQUIVO_FIXO}' and '{FOLDER_ID}' in parents and trashed = false"
        results = service.files().list(q=query, fields="files(id, name)").execute()
        items = results.get('files', [])
        
        if not items:
            return None
        return items[0]['id']
    except Exception as e:
        print(f"[BACKUP] ⚠️ Erro ao buscar arquivo no Drive: {e}")
        return None

def atualizar_drive(caminho_local):
    if not os.path.exists(SERVICE_ACCOUNT_FILE):
        print(f"[BACKUP] ❌ Arquivo de credenciais não encontrado: {SERVICE_ACCOUNT_FILE}")
        print("   -> Certifique-se de que 'service_account.json' está na raiz do projeto e montado no docker-compose.")
        return

    try:
        creds = service_account.Credentials.from_service_account_file(
            SERVICE_ACCOUNT_FILE, scopes=SCOPES
        )
        service = build('drive', 'v3', credentials=creds)

        file_id = buscar_arquivo_existente(service)
        
        if not file_id:
            print(f"[BACKUP] ⚠️ ARQUIVO '{NOME_ARQUIVO_FIXO}' NÃO ENCONTRADO NA PASTA {FOLDER_ID}!")
            print(f"   Ação necessária: Crie manualmente um arquivo vazio com esse nome na pasta do Google Drive.")
            return

        print(f"[BACKUP] Enviando para o Google Drive (ID: {file_id})...")
        
        media = MediaFileUpload(caminho_local, mimetype='application/sql', resumable=True)
        
        arquivo_atualizado = service.files().update(
            fileId=file_id,
            media_body=media,
            fields='id, version, size'
        ).execute()

        print(f"[BACKUP] ✅ SUCESSO TOTAL! Backup sincronizado. Versão: {arquivo_atualizado.get('version')}, Tamanho: {arquivo_atualizado.get('size')} bytes.")

    except Exception as e:
        print(f"[BACKUP] ❌ Erro na API do Google Drive: {e}")

def main():
    print(f"--- ROBÔ DE BACKUP [{datetime.datetime.now()}] ---")
    arquivo_sql = criar_dump_sql()
    
    if arquivo_sql:
        atualizar_drive(arquivo_sql)
        # Limpeza
        if os.path.exists(arquivo_sql):
            os.remove(arquivo_sql)
            print("[BACKUP] Arquivo temporário removido.")

if __name__ == '__main__':
    print("[SISTEMA] Serviço de Backup Iniciado. (Ciclo: 24h)")
    
    # Executa imediatamente na primeira vez para validar
    main()
    
    while True:
        print("[SISTEMA] Dormindo por 24 horas...")
        sys.stdout.flush() # Garante que o log apareça no Docker
        time.sleep(86400)
        main()