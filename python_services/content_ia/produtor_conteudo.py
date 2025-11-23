import time
import sys
import mysql.connector

# --- IMPORTS PADRÃO DOCKER ---
from python_services.shared.conectar_banco import criar_conexao_db
from python_services.content_ia.worker_conteudo import tarefa_gerar_conteudo

# Configurações
TEMPO_ESPERA_VAZIO = 10
TEMPO_RESPIRO = 2
TAMANHO_LOTE = 50

def main():
    print("--- [PRODUTOR CONTEÚDO] Iniciando... ---")
    
    while True:
        conn = None
        try:
            conn = criar_conexao_db()
            if not conn:
                time.sleep(30)
                continue
                
            cursor = conn.cursor(dictionary=True)

            # 1. Busca itens 'pendente'
            # Importante: Usa FOR UPDATE se possível ou apenas seleciona
            query = """
                SELECT id, palavra_chave_entrada, id_template_ia 
                FROM produtos 
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

            print(f"--- [PRODUTOR] Processando {len(itens)} itens... ---")
            
            # 2. Atualiza para 'processando'
            ids = [i['id'] for i in itens]
            format_strings = ','.join(['%s'] * len(ids))
            cursor.execute(f"UPDATE produtos SET status = 'processando' WHERE id IN ({format_strings})", tuple(ids))
            conn.commit()
            
            cursor.close()
            conn.close()

            # 3. Envia para a fila
            for item in itens:
                if not item.get('id_template_ia'):
                    print(f"⚠️ Produto {item['id']} sem template. Pulando.")
                    continue
                
                tarefa_gerar_conteudo.delay(item)
            
            print(f"✅ [PRODUTOR] {len(itens)} tarefas enviadas.")
            time.sleep(TEMPO_RESPIRO)

        except Exception as e:
            print(f"ERRO PRODUTOR: {e}")
            if conn and conn.is_connected(): conn.close()
            time.sleep(10)

if __name__ == "__main__":
    main()