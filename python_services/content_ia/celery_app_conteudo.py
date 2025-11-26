import os
import redis
from celery import Celery
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
    print(f"✅ [Conteúdo IA] Sentry iniciado.")
else:
    print(f"⚠️ [Conteúdo IA] Sentry NÃO configurado (DSN ausente).")

# --- 2. CONFIGURAÇÃO REDIS ---
# No Docker, o REDIS_URL vem automático. Localmente, usa fallback.
REDIS_URL = os.environ.get('REDIS_URL', 'redis://localhost:6379')

# --- 3. A FILA DE TAREFAS (Celery Broker - Banco 3) ---
celery_app_conteudo = Celery(
    'tarefas_conteudo',
    broker=f"{REDIS_URL}/3",  
    backend=f"{REDIS_URL}/3" 
)

# --- 4. FILA DE RESULTADOS (Redis Puro - Banco 4) ---
# O Worker escreve aqui, o Coletor lê daqui.
# (Esta é a variável que estava faltando!)
redis_conteudo_results_pool = redis.ConnectionPool.from_url(
    f"{REDIS_URL}/4",  
    decode_responses=True 
)

# --- 5. CONFIGURAÇÕES AVANÇADAS DO CELERY ---
celery_app_conteudo.conf.update(
    task_soft_time_limit=300,  # 5 minutos
    task_time_limit=360,       # 6 minutos
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    broker_connection_retry_on_startup=True,
    # [IMPORTANTE] Caminho absoluto para o worker
    imports=['python_services.content_ia.worker_conteudo']
)

# --- 6. DEFINIÇÃO DA FILA ---
celery_app_conteudo.conf.task_queues = (
    Queue('fila_conteudo', routing_key='conteudo.#'), 
)

# --- 7. ROTEAMENTO ---
celery_app_conteudo.conf.task_routes = {
    'python_services.content_ia.worker_conteudo.tarefa_gerar_conteudo': {
        'queue': 'fila_conteudo', 
        'routing_key': 'conteudo.gerar'
    },
}

if __name__ == '__main__':
    celery_app_conteudo.start()