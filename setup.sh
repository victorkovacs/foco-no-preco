#!/bin/bash

# --- CONFIGURAÇÃO INICIAL ---
echo "🚀 [SETUP] Iniciando instalação do Foco no Preço..."

# 1. Atualiza o Sistema
echo "🔄 [SISTEMA] Atualizando pacotes..."
sudo apt-get update && sudo apt-get upgrade -y
sudo apt-get install -y git curl

# 2. Instala Docker
if ! command -v docker &> /dev/null
then
    echo "🐳 [DOCKER] Instalando Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    echo "✅ [DOCKER] Instalado!"
else
    echo "✅ [DOCKER] Já instalado."
fi

# 3. Verifica Arquivos Críticos
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "📄 [CONFIG] Criando .env (Edite com suas senhas!)..."
        cp .env.example .env
    else
        echo "❌ [ERRO] .env.example não encontrado."
        exit 1
    fi
fi

if [ ! -f service_account.json ]; then
    echo "⚠️ [ALERTA] 'service_account.json' não encontrado na raiz!"
    echo "   Sem ele, não consigo baixar o backup do Drive."
    echo "   O sistema iniciará vazio."
fi

# 4. Sobe os Containers
echo "🏗️  [DOCKER] Subindo containers..."
sudo docker compose up -d --build

# 5. Aguarda Banco de Dados
echo "⏳ [DATABASE] Aguardando MySQL iniciar..."
until sudo docker compose exec db mysql -u root -p"${DB_PASSWORD}" -e 'SELECT 1' &> /dev/null; do
  echo "   ... aguardando db ..."
  sleep 5
done
echo "✅ [DATABASE] Conectado!"

# --- BLOCO DE RESTAURAÇÃO INTELIGENTE ---
RESTORE_OK=false

if [ -f service_account.json ]; then
    echo "📥 [BACKUP] Tentando baixar backup do Google Drive..."
    
    # Executa o script Python dentro do container 'worker_backup' (que já tem as libs do Google)
    if sudo docker compose exec worker_backup python python_services/backup/restore_drive.py; then
        
        echo "💾 [BACKUP] Restaurando banco de dados..."
        # Injeta o SQL baixado direto no MySQL
        # O arquivo restore_temp.sql está na pasta mapeada (raiz)
        cat restore_temp.sql | sudo docker compose exec -T db mysql -u root -p"${DB_PASSWORD}" foconopreco
        
        if [ $? -eq 0 ]; then
            echo "✅ [BACKUP] Banco restaurado com sucesso!"
            RESTORE_OK=true
            # Remove o arquivo temporário
            rm restore_temp.sql
        else
            echo "❌ [BACKUP] Falha na importação do SQL."
        fi
    else
        echo "⚠️ [BACKUP] Falha ao baixar ou nenhum backup encontrado."
    fi
else
    echo "⏭️ [BACKUP] Pular restauração (sem credenciais)."
fi

# 6. Finalização (Migrations)
if [ "$RESTORE_OK" = true ]; then
    echo "🔄 [LARAVEL] Rodando migrations apenas para garantir integridade..."
    sudo docker compose exec app php artisan migrate --force
else
    echo "🆕 [LARAVEL] Banco vazio. Criando tabelas do zero..."
    sudo docker compose exec app php artisan migrate --force
    sudo docker compose exec app php artisan db:seed --force
fi

echo "🧹 [SISTEMA] Limpando caches..."
sudo docker compose exec app php artisan optimize:clear

echo "--- 🎉 AMBIENTE PRONTO E RESTAURADO! ---"