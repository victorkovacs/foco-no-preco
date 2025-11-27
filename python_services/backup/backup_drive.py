import os
import time
import datetime
import subprocess
import sys
import mysql.connector
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload

# Tenta importar função de secrets ou usa fallback
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

# --- DADOS DO BANCO ---
DB_HOST = os.getenv('DB_HOST', 'db')
DB_NAME = os.getenv('DB_DATABASE', 'foconopreco')
DB_USER = get_docker_secret('db_user', os.getenv('DB_USERNAME', 'root'))
# Tenta ler do secret db_password, se falhar tenta db_root_password
DB_PASS = get_docker_secret('db_password', os.getenv('DB_PASSWORD'))
if not DB_PASS:
    DB_PASS = get_docker_secret('db_root_password')

SCOPES = ['https://www.googleapis.com/auth/drive']

def get_db_connection_simple():
    """Conexão rápida apenas para ler a configuração de horário"""
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME
    )

def criar_dump_sql():
    if not DB_PASS:
        print("[BACKUP] ❌ Erro: Senha do banco não encontrada.")
        return None

    caminho_completo = f"/tmp/{NOME_ARQUIVO_FIXO}"
    print(f"[BACKUP] Gerando dump do banco {DB_NAME}...")
    
    # Passa senha via ENV para segurança
    env_dump = os.environ.copy()
    env_dump['MYSQL_PWD'] = DB_PASS

    cmd = ['mysqldump', '--skip-ssl', '-h', DB_HOST, '-u', DB_USER, DB_NAME]

    try:
        with open(caminho_completo, 'w') as output_file:
            subprocess.run(cmd, env=env_dump, stdout=output_file, check=True)
        
        if os.path.getsize(caminho_completo) > 0:
            print(f"[BACKUP] Dump gerado: {caminho_completo}")
            return caminho_completo
        return None

    except Exception as e:
        print(f"[BACKUP] ❌ Erro no mysqldump: {e}")
        return None

def buscar_arquivo_existente(service):
    try:
        query = f"name = '{NOME_ARQUIVO_FIXO}' and '{FOLDER_ID}' in parents and trashed = false"
        results = service.files().list(q=query, fields="files(id, name)").execute()
        items = results.get('files', [])
        return items[0]['id'] if items else None
    except Exception as e:
        print(f"[BACKUP] ⚠️ Erro busca Drive: {e}")
        return None

def atualizar_drive(caminho_local):
    if not os.path.exists(SERVICE_ACCOUNT_FILE):
        print(f"[BACKUP] ❌ Arquivo de credenciais não encontrado.")
        return

    try:
        creds = service_account.Credentials.from_service_account_file(SERVICE_ACCOUNT_FILE, scopes=SCOPES)
        service = build('drive', 'v3', credentials=creds)

        file_id = buscar_arquivo_existente(service)
        
        media = MediaFileUpload(caminho_local, mimetype='application/sql', resumable=True)
        
        if file_id:
            print(f"[BACKUP] Atualizando arquivo existente (ID: {file_id})...")
            service.files().update(fileId=file_id, media_body=media).execute()
        else:
            print(f"[BACKUP] Criando novo arquivo no Drive...")
            file_metadata = {'name': NOME_ARQUIVO_FIXO, 'parents': [FOLDER_ID]}
            service.files().create(body=file_metadata, media_body=media).execute()

        print(f"[BACKUP] ✅ Upload concluído com sucesso!")

    except Exception as e:
        print(f"[BACKUP] ❌ Erro API Google: {e}")

def aguardar_horario_backup():
    """Lê configuração do banco e dorme até a hora do backup"""
    horario_padrao = '00:01'
    try:
        conn = get_db_connection_simple()
        cursor = conn.cursor()
        cursor.execute("SELECT valor FROM configuracoes_sistema WHERE chave = 'horario_backup'")
        row = cursor.fetchone()
        conn.close()
        horario_alvo = row[0] if row else horario_padrao
    except:
        horario_alvo = horario_padrao

    agora = datetime.datetime.now()
    try:
        h, m = map(int, horario_alvo.split(':'))
    except:
        h, m = 0, 1

    proximo = agora.replace(hour=h, minute=m, second=0, microsecond=0)
    
    if agora >= proximo:
        proximo += datetime.timedelta(days=1)
        
    segundos = (proximo - agora).total_seconds()
    horas = segundos / 3600
    
    print(f"--- [AGENDADOR BACKUP] Horário alvo: {horario_alvo} ---")
    print(f"--- [AGENDADOR BACKUP] Dormindo por {horas:.2f} horas... ---")
    
    time.sleep(segundos)

def main():
    print(f"--- ROBÔ DE BACKUP INICIADO ---")
    
    while True:
        # 1. Dorme até o horário configurado
        aguardar_horario_backup()
        
        # 2. Executa Backup
        print(f"[BACKUP] ⏰ Hora do backup! Iniciando...")
        arquivo = criar_dump_sql()
        if arquivo:
            atualizar_drive(arquivo)
            if os.path.exists(arquivo):
                os.remove(arquivo)
        
        # 3. Pausa segura para sair do minuto exato
        time.sleep(60)

if __name__ == '__main__':
    main()