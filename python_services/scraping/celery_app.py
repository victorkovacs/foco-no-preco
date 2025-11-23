import os
import redis
from celery import Celery
from kombu import Queue

# --- CONFIGURAÇÃO OBRIGATÓRIA ---
# No Docker, o REDIS_URL vem automático (redis://redis:6379). Localmente, usa localhost.
REDIS_URL = os.environ.get('REDIS_URL', 'redis://localhost:6379')

# 1. A FILA DE TAREFAS (Celery Broker - Banco 0)
celery_app = Celery(
    'tarefas_sistema',
    broker=f"{REDIS_URL}/0",
    backend=f"{REDIS_URL}/0"
)

# 2. AS FILAS DE RESULTADOS (Redis Puros - Usados pelos Coletores)
# db=1 -> Resultados do Scrape de Preço
redis_results_pool = redis.ConnectionPool.from_url(
    f"{REDIS_URL}/1",
    decode_responses=True
)

# db=2 -> Resultados da IA (Match)
redis_ia_results_pool = redis.ConnectionPool.from_url(
    f"{REDIS_URL}/2",
    decode_responses=True
)

# 3. CONFIGURAÇÕES AVANÇADAS DO CELERY
celery_app.conf.update(
    task_soft_time_limit=120,
    task_time_limit=180,
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    # --- IMPORTANTE: Diz ao Celery onde estão os arquivos de tarefa ---
    imports=[
        'python_services.scraping.worker',
        'python_services.scraping.worker_ia'
    ]
)

# 4. DEFINIÇÃO DAS FILAS
celery_app.conf.task_queues = (
    Queue('fila_scrape', routing_key='scrape.#'), # Fila para scraping de preços
    Queue('fila_ia',     routing_key='ia.#'),     # Fila para matching de IA
)

# 5. ROTEAMENTO DE TAREFAS (Explícito)
celery_app.conf.task_routes = {
    'python_services.scraping.worker.tarefa_scrape': {
        'queue': 'fila_scrape',
        'routing_key': 'scrape.tarefa'
    },
    'python_services.scraping.worker_ia.tarefa_match_ia': {
        'queue': 'fila_ia',
        'routing_key': 'ia.tarefa'
    },
}

print("Configuração do Celery (Scrape & IA) carregada com sucesso.")