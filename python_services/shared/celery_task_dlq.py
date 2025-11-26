import redis
import json
import os
import datetime
from celery import Task

# Configuração do Redis
REDIS_URL = os.getenv('REDIS_URL', 'redis://redis:6379')
DLQ_KEY = 'fila_dlq_erros'

class CeleryDLQTask(Task):
    """
    Classe base para tarefas Celery.
    Se a tarefa falhar definitivamente (após todos os retries),
    ela salva o contexto do erro em uma lista 'DLQ' no Redis.
    """
    
    # Garante que o Celery saiba que essa classe é abstrata e não uma tarefa em si
    abstract = True

    def on_failure(self, exc, task_id, args, kwargs, einfo):
        """
        Executado automaticamente pelo Celery quando a tarefa falha permanentemente.
        """
        print(f"[DLQ] 🚨 Tarefa {self.name} (ID: {task_id}) falhou definitivamente.")

        # 1. Monta o pacote de erro para análise humana
        erro_payload = {
            "task_id": task_id,
            "task_name": self.name,
            "args": args,
            "kwargs": kwargs,
            "error_type": type(exc).__name__,
            "error_message": str(exc),
            "traceback": str(einfo), 
            "failed_at": datetime.datetime.now().isoformat(),
            "worker_hostname": self.request.hostname
        }

        # 2. Salva na fila DLQ do Redis
        try:
            # --- CORREÇÃO AQUI: 'from_url' deve ser minúsculo ---
            r = redis.from_url(REDIS_URL)
            r.lpush(DLQ_KEY, json.dumps(erro_payload, default=str))
            print(f"[DLQ] ✅ Erro salvo na lista '{DLQ_KEY}' para análise.")
            r.close() 
        except Exception as e:
            print(f"[DLQ] ❌ CRÍTICO: Falha ao salvar na DLQ: {e}")

        # Chama a implementação padrão (logging do celery)
        super().on_failure(exc, task_id, args, kwargs, einfo)