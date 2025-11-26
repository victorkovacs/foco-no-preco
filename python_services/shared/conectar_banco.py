import os
import sys
import mysql.connector
from mysql.connector import errorcode
from dotenv import load_dotenv

# -------------------------------------------------------------------------
# 1. CARREGAMENTO DAS VARIÁVEIS DE AMBIENTE (.env)
# -------------------------------------------------------------------------

diretorio_atual = os.path.dirname(os.path.abspath(__file__))
caminho_env = os.path.join(os.path.dirname(os.path.dirname(diretorio_atual)), '.env')

if os.path.exists(caminho_env):
    load_dotenv(caminho_env)

# -------------------------------------------------------------------------
# 2. FUNÇÃO AUXILIAR PARA LER SECRETS
# -------------------------------------------------------------------------
def get_docker_secret(secret_name, default=None):
    """
    Tenta ler o conteúdo de um arquivo Docker Secret em /run/secrets.
    Se falhar, retorna o valor default.
    """
    try:
        with open(f'/run/secrets/{secret_name}', 'r') as file:
            return file.read().strip()
    except IOError:
        return default

# -------------------------------------------------------------------------
# 3. CONFIGURAÇÃO DA CONEXÃO
# -------------------------------------------------------------------------

# Lê a senha do secret, ou cai para o env var DB_PASSWORD, ou vazio.
senha_banco = get_docker_secret('db_password', os.getenv('DB_PASSWORD', ''))

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_DATABASE', 'foconopreco'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': senha_banco,
    'port': int(os.getenv('DB_PORT', 3306)),
    # --- CORREÇÃO CRÍTICA PARA MYSQL 8 ---
    # Removemos 'ssl_disabled': True
    # Adicionamos ssl_verify_cert: False para aceitar certificado auto-assinado do Docker
    'ssl_verify_cert': False
}

# -------------------------------------------------------------------------
# 4. FUNÇÃO DE CONEXÃO
# -------------------------------------------------------------------------

def criar_conexao_db():
    conn = None
    try:
        # Tenta conectar (agora permitindo SSL implícito)
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn

    except mysql.connector.Error as err:
        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            print(f"ERRO DE ACESSO: Usuário '{DB_CONFIG['user']}' rejeitado no banco '{DB_CONFIG['database']}'.")
        elif err.errno == errorcode.ER_BAD_DB_ERROR:
            print(f"ERRO DE BANCO: O banco de dados '{DB_CONFIG['database']}' não existe.")
        else:
            print(f"ERRO DE CONEXÃO: {err}")
        return None
        
    except Exception as e:
        print(f"ERRO INESPERADO AO CONECTAR: {e}")
        return None

# -------------------------------------------------------------------------
# 5. BLOCO DE TESTE
# -------------------------------------------------------------------------
if __name__ == "__main__":
    print("--- INICIANDO TESTE DE CONEXÃO (FIX MYSQL 8) ---")
    print(f"Host: {DB_CONFIG['host']}")
    print(f"Banco: {DB_CONFIG['database']}")
    
    conexao = criar_conexao_db()
    
    if conexao and conexao.is_connected():
        print("✅ SUCESSO! Conexão estabelecida com o banco de dados.")
        conexao.close()
    else:
        print("❌ FALHA! Não foi possível conectar.")