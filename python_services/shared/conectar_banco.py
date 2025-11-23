import os
import sys
import mysql.connector
from mysql.connector import errorcode
from dotenv import load_dotenv

# -------------------------------------------------------------------------
# 1. CARREGAMENTO DAS VARIÁVEIS DE AMBIENTE (.env)
# -------------------------------------------------------------------------

# Identifica onde este arquivo (conectar_banco.py) está
diretorio_atual = os.path.dirname(os.path.abspath(__file__))

# Sobe 2 níveis para achar a raiz do Laravel:
# shared -> python_services -> RAIZ DO PROJETO
caminho_env = os.path.join(os.path.dirname(os.path.dirname(diretorio_atual)), '.env')

# Tenta carregar o arquivo .env
if os.path.exists(caminho_env):
    load_dotenv(caminho_env)
else:
    # Se não achar o arquivo exato, confia que o Docker já injetou as variáveis
    print(f"AVISO: Arquivo .env não encontrado no caminho esperado: {caminho_env}")
    print("Tentando usar variáveis de ambiente do sistema/container...")

# -------------------------------------------------------------------------
# 2. CONFIGURAÇÃO DA CONEXÃO
# -------------------------------------------------------------------------

# Lê as variáveis. Se não existirem, usa um valor padrão (fallback)
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_DATABASE', 'foconopreco'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'port': int(os.getenv('DB_PORT', 3306)),
    # 'ssl_disabled' ajuda a evitar erros de handshake em alguns ambientes locais/docker
    'ssl_disabled': True
}

# -------------------------------------------------------------------------
# 3. FUNÇÃO DE CONEXÃO
# -------------------------------------------------------------------------

def criar_conexao_db():
    """
    Cria e retorna uma conexão com o banco de dados MySQL
    usando as configurações lidas do arquivo .env do Laravel.
    """
    conn = None
    try:
        # Tenta conectar
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn

    except mysql.connector.Error as err:
        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            print(f"ERRO DE ACESSO: Usuário ou senha inválidos para o banco '{DB_CONFIG['database']}'.")
            print("Verifique se as credenciais no arquivo .env estão corretas.")
        elif err.errno == errorcode.ER_BAD_DB_ERROR:
            print(f"ERRO DE BANCO: O banco de dados '{DB_CONFIG['database']}' não existe.")
        else:
            print(f"ERRO DE CONEXÃO: {err}")
        return None
        
    except Exception as e:
        print(f"ERRO INESPERADO AO CONECTAR: {e}")
        return None

# -------------------------------------------------------------------------
# 4. BLOCO DE TESTE (Roda apenas se chamar o arquivo direto)
# -------------------------------------------------------------------------
if __name__ == "__main__":
    print("--- INICIANDO TESTE DE CONEXÃO ---")
    print(f"Lendo configurações de: {caminho_env if os.path.exists(caminho_env) else 'Variáveis de Ambiente'}")
    print(f"Host: {DB_CONFIG['host']}")
    print(f"Banco: {DB_CONFIG['database']}")
    print(f"Usuário: {DB_CONFIG['user']}")
    
    conexao = criar_conexao_db()
    
    if conexao and conexao.is_connected():
        print("✅ SUCESSO! Conexão estabelecida com o banco de dados.")
        conexao.close()
    else:
        print("❌ FALHA! Não foi possível conectar.")