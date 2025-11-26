import redis
import json
import os
import sys
from pprint import pprint

# Configuração
REDIS_URL = os.getenv('REDIS_URL', 'redis://redis:6379')
DLQ_KEY = 'fila_dlq_erros'

def ver_erros():
    try:
        r = redis.Redis.from_url(REDIS_URL)
        qtd = r.llen(DLQ_KEY)
        
        print(f"\n=== MONITOR DLQ (Total: {qtd} erros) ===")
        
        if qtd == 0:
            print("✅ Nenhuma tarefa morta na fila.")
            return

        print("1. Ver último erro")
        print("2. Baixar todos para JSON (backup)")
        print("3. Limpar DLQ (Apagar tudo)")
        print("0. Sair")
        
        opcao = input("\nEscolha: ")

        if opcao == '1':
            # Pega o primeiro da lista sem remover (lindex 0)
            raw = r.lindex(DLQ_KEY, 0)
            if raw:
                dados = json.loads(raw)
                print("\n--- ÚLTIMO ERRO ---")
                pprint(dados)
                print("-------------------")
        
        elif opcao == '2':
            # Baixa tudo
            todos = r.lrange(DLQ_KEY, 0, -1)
            lista_final = [json.loads(x) for x in todos]
            filename = "dump_dlq_erros.json"
            with open(filename, 'w', encoding='utf-8') as f:
                json.dump(lista_final, f, indent=4, ensure_ascii=False)
            print(f"✅ Salvo em {filename}")

        elif opcao == '3':
            confirm = input("Tem certeza? Digite 'SIM': ")
            if confirm == 'SIM':
                r.delete(DLQ_KEY)
                print("🗑️ DLQ Limpa.")

    except Exception as e:
        print(f"Erro ao conectar no Redis: {e}")

if __name__ == "__main__":
    ver_erros()