import os
import sys
import mysql.connector
import redis
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
    Se falhar, retorna o valor default ou da variável de ambiente.
    """
    try:
        with open(f'/run/secrets/{secret_name}', 'r') as file:
            return file.read().strip()
    except IOError:
        return os.getenv(secret_name.upper(), default)

# -------------------------------------------------------------------------
# 3. CONFIGURAÇÃO REDIS (CENTRALIZADA)
# -------------------------------------------------------------------------
# Define a URL base do Redis (Docker ou Local)
REDIS_URL = os.getenv('REDIS_URL', 'redis://redis:6379')

# Pool de Resultados do Scrape (DB 1)
redis_results_pool = redis.ConnectionPool.from_url(
    f"{REDIS_URL}/1",
    decode_responses=True
)

# Pool de Resultados da IA - Match (DB 2)
redis_ia_results_pool = redis.ConnectionPool.from_url(
    f"{REDIS_URL}/2",
    decode_responses=True
)

# Pool de Resultados de Conteúdo (DB 4)
redis_conteudo_results_pool = redis.ConnectionPool.from_url(
    f"{REDIS_URL}/4",
    decode_responses=True
)

# -------------------------------------------------------------------------
# 4. CONFIGURAÇÃO DA CONEXÃO MYSQL
# -------------------------------------------------------------------------

# Lê a senha do secret, ou cai para o env var DB_PASSWORD, ou vazio.
senha_banco = get_docker_secret('db_password', os.getenv('DB_PASSWORD', ''))

DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_DATABASE', 'foconopreco'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': senha_banco,
    'port': int(os.getenv('DB_PORT', 3306)),
    # Permite conexão SSL implícita (necessário para MySQL 8 no Docker)
    'ssl_verify_cert': False
}

def criar_conexao_db():
    conn = None
    try:
        # Tenta conectar
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn

    except mysql.connector.Error as err:
        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            print(f"❌ [DB] ERRO DE ACESSO: Usuário '{DB_CONFIG['user']}' negado.")
        elif err.errno == errorcode.ER_BAD_DB_ERROR:
            print(f"❌ [DB] ERRO DE BANCO: O banco '{DB_CONFIG['database']}' não existe.")
        else:
            print(f"❌ [DB] ERRO DE CONEXÃO: {err}")
        return None
        
    except Exception as e:
        print(f"❌ [DB] ERRO INESPERADO: {e}")
        return None

# -------------------------------------------------------------------------
# 5. BLOCO DE TESTE
# -------------------------------------------------------------------------
if __name__ == "__main__":
    print("--- INICIANDO TESTE DE CONEXÃO (SHARED) ---")
    print(f"Host: {DB_CONFIG['host']}")
    
    conexao = criar_conexao_db()
    
    if conexao and conexao.is_connected():
        print("✅ SUCESSO! Conexão MySQL estabelecida.")
        conexao.close()
    else:
        print("❌ FALHA! Não foi possível conectar.")