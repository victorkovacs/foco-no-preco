import os
import redis
from celery import Celery
from celery.schedules import crontab
from kombu import Queue
import sentry_sdk
from sentry_sdk.integrations.celery import CeleryIntegration

# --- 1. CONFIGURAÇÃO SENTRY (Monitoramento) ---
SENTRY_DSN = os.getenv('SENTRY_DSN')

if SENTRY_DSN:
    sentry_sdk.init(
        dsn=SENTRY_DSN,
        integrations=[CeleryIntegration()],
        traces_sample_rate=1.0,
        environment="production",
        send_default_pii=True
    )
    print(f"✅ [Scraping] Sentry iniciado.")
else:
    print(f"⚠️ [Scraping] Sentry NÃO configurado (DSN ausente).")

# --- 2. CONFIGURAÇÃO REDIS ---
REDIS_URL = os.getenv('REDIS_URL', 'redis://redis:6379')

# --- 3. DEFINIÇÃO DO APP CELERY (Nome da variável deve ser 'celery_app') ---
celery_app = Celery(
    'tarefas_sistema',
    broker=f"{REDIS_URL}/0",
    backend=f"{REDIS_URL}/0",
    include=[
        'python_services.scraping.worker',
        'python_services.scraping.worker_ia'
    ]
)

# --- 4. POOLS DE CONEXÃO REDIS (Necessários para o worker.py) ---
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

# --- 5. CONFIGURAÇÕES AVANÇADAS ---
celery_app.conf.update(
    task_soft_time_limit=120,
    task_time_limit=180,
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    result_expires=3600,
    timezone='America/Sao_Paulo',
    enable_utc=True,
    task_serializer='json',
    accept_content=['json'],
    result_serializer='json',
    broker_connection_retry_on_startup=True,
)

# --- 6. FILAS E ROTAS ---
celery_app.conf.task_queues = (
    Queue('fila_scrape', routing_key='scrape.#'),
    Queue('fila_ia',     routing_key='ia.#'),
)

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

# --- 7. AGENDAMENTO (BEAT) ---
celery_app.conf.beat_schedule = {
    'verificar-fila-dlq-cada-10-min': {
        'task': 'python_services.scraping.worker.verificar_dlq_task',
        'schedule': crontab(minute='*/10'),
    },
}

if __name__ == '__main__':
    celery_app.start()