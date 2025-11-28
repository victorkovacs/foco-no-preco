import sys
import json
import requests
from bs4 import BeautifulSoup
import re
import random
import traceback
import unicodedata

# Configuração de User-Agents
USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
]

def limpar_preco(texto):
    if not texto: return None
    # Remove R$, pontos de milhar e espaços
    texto_limpo = texto.upper().replace('R$', '').replace('.', '').replace(' ', '').strip()
    # Troca vírgula decimal por ponto
    texto_limpo = texto_limpo.replace(',', '.')
    
    # Extrai apenas números e o ponto decimal
    match = re.search(r'(\d+\.?\d*)', texto_limpo)
    if match:
        try:
            return float(match.group(1))
        except:
            return None
    return None

def testar_extração(url, seletor_preco, desconto_percentual=0):
    headers = {
        'User-Agent': random.choice(USER_AGENTS),
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
    }

    try:
        response = requests.get(url, headers=headers, timeout=15)
        response.raise_for_status()
        
        soup = BeautifulSoup(response.content, 'html.parser')
        
        resultado = {
            "success": False,
            "preco_bruto": None,
            "preco_final": None,
            "error_message": None
        }

        # Tenta encontrar o elemento
        elementos = soup.select(seletor_preco)
        
        if not elementos:
            resultado["error_message"] = f"Seletor '{seletor_preco}' não encontrou nenhum elemento na página."
            return resultado

        # Pega o texto do primeiro elemento encontrado
        texto_preco = elementos[0].get_text(strip=True)
        resultado["preco_bruto"] = texto_preco
        
        # Converte para Float
        valor_float = limpar_preco(texto_preco)
        
        if valor_float is not None:
            # [CORREÇÃO] Aplica o desconto matemático aqui
            if desconto_percentual > 0:
                valor_float = valor_float * (1 - (desconto_percentual / 100))
            
            resultado["success"] = True
            resultado["preco_final"] = round(valor_float, 2)
        else:
            resultado["error_message"] = f"Elemento encontrado ('{texto_preco}'), mas não foi possível converter para número."

        return resultado

    except requests.exceptions.RequestException as e:
        return {
            "success": False,
            "error_message": f"Erro de conexão: {str(e)}"
        }
    except Exception as e:
        return {
            "success": False,
            "error_message": f"Erro interno: {str(e)}",
            "python_error_details": traceback.format_exc()
        }

if __name__ == "__main__":
    try:
        # Esperamos pelo menos 5 argumentos (script + 4 parametros obrigatórios)
        # Argumentos: script.py <URL> <URL_Ignorada> <Ignorado> <Seletor> <Ignorado> <Desconto>
        if len(sys.argv) < 5:
            print(json.dumps({"success": False, "message": "Argumentos insuficientes para o script Python."}))
            sys.exit(1)

        url_alvo = sys.argv[1]
        seletor_preco = sys.argv[4]
        
        # [CORREÇÃO] Lê o desconto do 6º argumento (índice 6)
        desconto = 0.0
        if len(sys.argv) >= 7:
            try:
                desconto = float(sys.argv[6])
            except:
                desconto = 0.0
        
        resultado = testar_extração(url_alvo, seletor_preco, desconto)
        
        print(json.dumps(resultado))

    except Exception as e:
        print(json.dumps({
            "success": False,
            "message": f"Erro fatal no script Python: {str(e)}",
            "python_error_details": traceback.format_exc()
        }))