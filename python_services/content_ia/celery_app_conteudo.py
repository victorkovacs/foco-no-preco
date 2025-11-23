import os
import redis
from celery import Celery
from kombu import Queue 

# --- CONFIGURAÇÃO OBRIGATÓRIA ---
# No Docker, o REDIS_URL vem automático. Localmente, usa fallback.
REDIS_URL = os.environ.get('REDIS_URL', 'redis://localhost:6379')

# 1. A FILA DE TAREFAS (Celery Broker - Banco 3)
celery_app_conteudo = Celery(
    'tarefas_conteudo',
    broker=f"{REDIS_URL}/3",  
    backend=f"{REDIS_URL}/3" 
)

# 2. FILA DE RESULTADOS (Redis Puro - Banco 4)
# O Worker escreve aqui, o Coletor lê daqui.
redis_conteudo_results_pool = redis.ConnectionPool.from_url(
    f"{REDIS_URL}/4",  
    decode_responses=True 
)

# 3. CONFIGURAÇÕES AVANÇADAS DO CELERY
celery_app_conteudo.conf.update(
    task_soft_time_limit=300,  # 5 minutos
    task_time_limit=360,       # 6 minutos
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    # [IMPORTANTE] Caminho absoluto para o worker
    imports=['python_services.content_ia.worker_conteudo']
)

# 4. DEFINIÇÃO DA FILA
celery_app_conteudo.conf.task_queues = (
    Queue('fila_conteudo', routing_key='conteudo.#'), 
)

# 5. ROTEAMENTO
celery_app_conteudo.conf.task_routes = {
    'python_services.content_ia.worker_conteudo.tarefa_gerar_conteudo': {
        'queue': 'fila_conteudo', 
        'routing_key': 'conteudo.gerar'
    },
}

print("Configuração do Celery (Conteúdo) carregada com sucesso.")