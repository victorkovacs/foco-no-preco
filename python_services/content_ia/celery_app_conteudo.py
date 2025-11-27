import os
import redis
from celery import Celery
from kombu import Queue 
import sentry_sdk
from sentry_sdk.integrations.celery import CeleryIntegration

# --- 1. CONFIGURAÇÃO SENTRY ---
SENTRY_DSN = os.getenv('SENTRY_DSN')

if SENTRY_DSN:
    sentry_sdk.init(
        dsn=SENTRY_DSN,
        integrations=[CeleryIntegration()],
        traces_sample_rate=1.0,
        environment="production",
        send_default_pii=True
    )
    print(f"✅ [Conteúdo IA] Sentry iniciado.")
else:
    print(f"⚠️ [Conteúdo IA] Sentry NÃO configurado (DSN ausente).")

# --- 2. CONFIGURAÇÃO REDIS ---
REDIS_URL = os.environ.get('REDIS_URL', 'redis://localhost:6379')

# --- 3. A FILA DE TAREFAS (Celery Broker - Banco 3) ---
celery_app_conteudo = Celery(
    'tarefas_conteudo',
    broker=f"{REDIS_URL}/3",  
    backend=f"{REDIS_URL}/3" 
)

# NOTA: O 'redis_conteudo_results_pool' foi movido para shared/conectar_banco.py

# --- 4. CONFIGURAÇÕES AVANÇADAS ---
celery_app_conteudo.conf.update(
    task_soft_time_limit=300,
    task_time_limit=360,
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    broker_connection_retry_on_startup=True,
)

# --- 5. DEFINIÇÃO DA FILA ---
celery_app_conteudo.conf.task_queues = (
    Queue('fila_conteudo', routing_key='conteudo.#'), 
)

# --- 6. ROTEAMENTO ---
celery_app_conteudo.conf.task_routes = {
    'python_services.content_ia.worker_conteudo.tarefa_gerar_conteudo': {
        'queue': 'fila_conteudo', 
        'routing_key': 'conteudo.gerar'
    },
}

# --- 7. IMPORTAÇÃO DOS WORKERS ---
celery_app_conteudo.conf.update(
    imports=['python_services.content_ia.worker_conteudo']
)

if __name__ == '__main__':
    celery_app_conteudo.start()