@extends('layouts.app')

@section('title', 'Revalidação Manual da IA')

@section('content')
<div class="w-full max-w-7xl mx-auto bg-white shadow-xl rounded-2xl p-6 md:p-8 my-8">
    
    <h1 class="text-3xl md:text-4xl font-bold text-gray-700 mb-4 flex items-center">
        <i data-lucide="bot" class="mr-3 text-primary-dark"></i>
        Revalidação Manual da IA
    </h1>
    
    <p class="text-lg text-gray-600 mb-6">
        Cole a lista de SKUs que você deseja reprocessar. A IA irá buscar novos concorrentes para eles na próxima rodada.
    </p>

    <div id="message-container" class="hidden mb-6"></div>

    <div class="mb-6">
        <label for="skus_textarea" class="block text-sm font-medium text-gray-700 mb-2">
            Lista de SKUs (um por linha ou separados por vírgula):
        </label>
        <textarea id="skus_textarea" rows="10" 
                  class="p-4 block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-dark focus:border-primary-dark font-mono"
                  placeholder="SKU123&#10;SKU456&#10;SKU789"></textarea>
        <p class="text-xs text-gray-500 mt-2 text-right">Insira um SKU por linha.</p>
    </div>

    <button id="submit_button" class="text-white bg-primary-dark hover:bg-primary-darker focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-lg px-8 py-3.5 me-2 mb-2 focus:outline-none shadow-lg transition-all duration-200 w-full md:w-auto flex items-center justify-center">
        <span id="btn_text">Enviar para Fila da IA</span>
        <i id="loading_spinner" data-lucide="loader-2" class="ml-2 w-5 h-5 animate-spin hidden"></i>
    </button>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
        
        const submitButton = document.getElementById('submit_button');
        const textarea = document.getElementById('skus_textarea');
        const messageContainer = document.getElementById('message-container');
        const loadingSpinner = document.getElementById('loading_spinner');
        
        submitButton.addEventListener('click', () => {
            const rawText = textarea.value;
            
            // Processa o texto: quebra por linha ou vírgula e limpa espaços
            const skus = rawText.split(/[\n,]+/)
                                .map(s => s.trim())
                                .filter(s => s.length > 0);

            if (skus.length === 0) {
                showMessage('Por favor, insira pelo menos um SKU válido.', 'error');
                return;
            }

            setLoading(true);

            // Envio para o Laravel
            fetch("{{ route('ia_manual.process') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({ skus: skus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    textarea.value = ''; // Limpa o campo
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showMessage('Erro crítico ao comunicar com o servidor.', 'error');
            })
            .finally(() => {
                setLoading(false);
            });
        });

        function setLoading(isLoading) {
            if (isLoading) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-70', 'cursor-not-allowed');
                loadingSpinner.classList.remove('hidden');
            } else {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
                loadingSpinner.classList.add('hidden');
            }
        }

        function showMessage(message, type = 'success') {
            messageContainer.innerHTML = '';
            messageContainer.classList.remove('hidden');
            
            const alertClass = (type === 'success') 
                ? 'bg-green-50 border-green-500 text-green-700' 
                : 'bg-red-50 border-red-500 text-red-700';
            
            const icon = (type === 'success') ? 'check-circle' : 'alert-circle';

            messageContainer.innerHTML = `
                <div class="border-l-4 p-4 rounded-md ${alertClass} flex items-start shadow-sm">
                    <i data-lucide="${icon}" class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <p class="font-bold">${type === 'success' ? 'Sucesso!' : 'Erro'}</p>
                        <p class="text-sm mt-1">${message}</p>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
    });
</script>
@endsection