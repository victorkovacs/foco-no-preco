import time
import sys
import mysql.connector
import json

# --- IMPORTS PADRÃO DOCKER ---
from python_services.shared.conectar_banco import criar_conexao_db
from python_services.content_ia.worker_conteudo import tarefa_gerar_conteudo

# Configurações
TEMPO_ESPERA_VAZIO = 10
TEMPO_RESPIRO = 2
TAMANHO_LOTE = 50

def main():
    print("--- [PRODUTOR CONTEÚDO] Iniciando (Fila Isolada)... ---")
    
    while True:
        conn = None
        try:
            conn = criar_conexao_db()
            if not conn:
                time.sleep(30)
                continue
                
            cursor = conn.cursor(dictionary=True)

            # 1. Busca tarefas pendentes na Fila Isolada
            # Agora trazemos SKU e Nome para o log ficar bonito
            query = """
                SELECT id, id_produto, sku, nome_produto, palavra_chave_entrada, id_template_ia 
                FROM FilaGeracaoConteudo 
                WHERE status = 'pendente' 
                ORDER BY id ASC LIMIT %s
            """
            cursor.execute(query, (TAMANHO_LOTE,))
            itens = cursor.fetchall()
            
            if not itens:
                cursor.close()
                conn.close()
                time.sleep(TEMPO_ESPERA_VAZIO)
                continue

            print(f"--- [PRODUTOR] Processando {len(itens)} itens da fila... ---")
            
            # 2. Atualiza status para 'processando'
            ids_fila = [i['id'] for i in itens]
            format_strings = ','.join(['%s'] * len(ids_fila))
            cursor.execute(f"UPDATE FilaGeracaoConteudo SET status = 'processando' WHERE id IN ({format_strings})", tuple(ids_fila))
            conn.commit()
            
            cursor.close()
            conn.close()

            # 3. Envia para o Worker (Celery)
            for item in itens:
                print(f"   -> Enviando: {item['sku']} - {item['nome_produto']}")
                
                payload = {
                    'id': item['id_produto'],       # Mantemos ID numérico para referência final
                    'id_tarefa_fila': item['id'],   # ID da Fila (Para o Coletor dar baixa)
                    'sku': item['sku'],             # Metadado útil
                    'nome_produto': item['nome_produto'], # Metadado útil
                    'palavra_chave_entrada': item['palavra_chave_entrada'], # O que a IA vai ler
                    'id_template_ia': item['id_template_ia']
                }
                
                tarefa_gerar_conteudo.delay(payload)
            
            print(f"✅ [PRODUTOR] Lote enviado.")
            time.sleep(TEMPO_RESPIRO)

        except Exception as e:
            print(f"❌ ERRO PRODUTOR: {e}")
            if conn and conn.is_connected(): conn.close()
            time.sleep(10)

if __name__ == "__main__":
    main()