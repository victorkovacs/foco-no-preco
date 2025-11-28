@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 md:p-8 max-w-7xl">
    
    {{-- Header da Página --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Gestão de Templates (IA)</h1>
            <p class="text-gray-500 mt-1">Configure a "personalidade" e a estrutura de saída da sua IA.</p>
        </div>
        <button onclick="abrirModalNovo()" class="group relative inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white transition-all duration-200 bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-md hover:shadow-lg hover:-translate-y-0.5">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Adicionar Novo Template
        </button>
    </div>

    {{-- Feedback de Sucesso --}}
    @if(session('success'))
        <div id="flash-message" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex justify-between items-center animate-fade-in-down">
            <div>
                <p class="font-bold">Sucesso!</p>
                <p>{{ session('success') }}</p>
            </div>
            <button onclick="document.getElementById('flash-message').remove()" class="text-green-600 hover:text-green-800 font-bold px-2">×</button>
        </div>
    @endif

    {{-- Tabela de Listagem --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nome do Template</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Última Atualização</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($templates as $template)
                    <tr class="hover:bg-blue-50/30 transition-colors duration-150 group">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">#{{ $template->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-blue-700 transition-colors">{{ $template->nome_template }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $template->updated_at ? $template->updated_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="abrirModalEditar({{ $template->id }})" class="text-indigo-600 hover:text-indigo-900 font-semibold mr-4 hover:underline transition-all">Editar</button>
                            <form action="{{ route('templates_ia.destroy', $template->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este template permanentemente?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold hover:underline transition-all">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-gray-50 p-4 rounded-full mb-3">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <p class="text-lg font-medium text-gray-900">Nenhum template encontrado</p>
                                <p class="text-sm text-gray-500 mt-1">Comece criando o primeiro template para sua IA.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            {{ $templates->links() }}
        </div>
    </div>
</div>

{{-- MODAL "CINEMA" (Visual Restaurado) --}}
<div id="modal-template" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    {{-- Backdrop Escuro com Blur --}}
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="fecharModal()"></div>

    {{-- Container Central --}}
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            
            {{-- Painel do Modal (Largo e Moderno) --}}
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-7xl border border-gray-200 flex flex-col max-h-[95vh]">
                
                {{-- Header Fixo --}}
                <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center sticky top-0 z-20">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900" id="modal-titulo">Novo Template</h3>
                        <p class="text-sm text-gray-500">Defina as regras de comportamento da IA.</p>
                    </div>
                    <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                {{-- Conteúdo Scrollável (Formulário) --}}
                <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-gray-50/50">
                    <form id="form-template" class="h-full">
                        <input type="hidden" id="template_id" name="id">

                        {{-- SEÇÃO IA (Card de Engenharia Reversa) --}}
                        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl p-6 mb-8 border border-indigo-100 shadow-sm relative overflow-hidden group">
                            <div class="relative z-10">
                                <label class="block text-sm font-bold text-indigo-900 mb-2 flex items-center gap-2">
                                    <span class="bg-indigo-600 text-white p-1 rounded-md shadow-sm">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    </span>
                                    Gerador de Prompt Automático (Engenharia Reversa)
                                </label>
                                <p class="text-sm text-indigo-700/80 mb-4 max-w-3xl">
                                    Não sabe como escrever o prompt? Cole um exemplo <b>perfeito</b> do texto final que você deseja. A IA analisará o exemplo e escreverá as instruções técnicas para você.
                                </p>
                                
                                <div class="flex flex-col md:flex-row gap-4 items-start">
                                    <div class="flex-1 w-full relative">
                                        <textarea id="exemplo_saida" rows="3" class="w-full rounded-lg border-indigo-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm shadow-sm transition-shadow placeholder-indigo-300 bg-white/80" placeholder="Ex: Cole aqui um post de blog pronto, um JSON de produto, ou um e-mail de vendas que servirá de modelo..."></textarea>
                                    </div>
                                    <button type="button" id="btn-gerar-automatico" onclick="gerarAutomatico()" class="md:self-start inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all active:scale-95 whitespace-nowrap h-full">
                                        <span>🪄 Gerar com IA</span>
                                    </button>
                                </div>
                                <div id="loading-ia" class="hidden mt-3 flex items-center text-sm font-medium text-indigo-600 animate-pulse">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Analisando exemplo e criando prompt...
                                </div>
                            </div>
                        </div>

                        {{-- FORMULÁRIO PRINCIPAL --}}
                        <div class="space-y-6">
                            {{-- Nome --}}
                            <div>
                                <label for="nome_template" class="block text-sm font-bold text-gray-700 mb-1">Nome do Template <span class="text-red-500">*</span></label>
                                <input type="text" id="nome_template" name="nome_template" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-3 text-lg font-medium text-gray-800 placeholder-gray-400 transition-shadow" placeholder="Ex: Descrição SEO para E-commerce">
                            </div>

                            {{-- Grid Lado a Lado (Campos Gigantes) --}}
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                {{-- Prompt --}}
                                <div class="flex flex-col h-full">
                                    <div class="flex justify-between items-center mb-2">
                                        <label for="prompt_sistema" class="block text-sm font-bold text-gray-700">Prompt de Sistema (Instruções) <span class="text-red-500">*</span></label>
                                        <span class="text-xs font-mono text-gray-500 bg-gray-200 px-2 py-1 rounded border border-gray-300">System Message</span>
                                    </div>
                                    <div class="flex-1 relative">
                                        <textarea id="prompt_sistema" name="prompt_sistema" required rows="20" class="block w-full h-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-slate-50 font-mono text-xs leading-relaxed text-slate-700 p-4 resize-y" placeholder="Você é um especialista em... (Defina aqui como a IA deve se comportar)"></textarea>
                                    </div>
                                </div>
                                
                                {{-- Schema --}}
                                <div class="flex flex-col h-full">
                                    <div class="flex justify-between items-center mb-2">
                                        <label for="json_schema_saida" class="block text-sm font-bold text-gray-700">JSON Schema (Estrutura)</label>
                                        <span class="text-xs font-mono text-gray-500 bg-gray-200 px-2 py-1 rounded border border-gray-300">Structured Output</span>
                                    </div>
                                    <div class="flex-1 relative">
                                        <textarea id="json_schema_saida" name="json_schema_saida" rows="20" class="block w-full h-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-slate-50 font-mono text-xs leading-relaxed text-slate-700 p-4 resize-y" placeholder="{ &quot;type&quot;: &quot;object&quot;, ... } (Opcional: Força a IA a responder em JSON rígido)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                {{-- Footer Fixo --}}
                <div class="bg-white px-6 py-4 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-20 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                    <button type="button" onclick="fecharModal()" class="px-6 py-2.5 bg-white text-gray-700 text-sm font-bold rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="salvarTemplate()" id="btn-salvar-template" class="px-6 py-2.5 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-lg hover:shadow-xl transition-all transform active:scale-95 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Salvar Template
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Configurações e Rotas
    const CONFIG = {
        rotaSalvar: "{{ route('templates_ia.store') }}",
        rotaGerar: "{{ route('templates_ia.gerar_auto') }}",
        rotaShow: "{{ url('templates-ia') }}", 
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };

    // --- MANIPULAÇÃO DO MODAL ---
    function abrirModalNovo() {
        document.getElementById('modal-titulo').innerText = 'Novo Template';
        document.getElementById('template_id').value = '';
        document.getElementById('form-template').reset();
        document.getElementById('exemplo_saida').value = '';
        
        // Remove erros visuais anteriores
        document.querySelectorAll('.ring-red-500').forEach(el => el.classList.remove('ring-2', 'ring-red-500'));
        
        toggleModal(true);
    }

    function fecharModal() {
        toggleModal(false);
    }

    function toggleModal(show) {
        const modal = document.getElementById('modal-template');
        if(show) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    // --- CARREGAR DADOS ---
    async function abrirModalEditar(id) {
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="animate-pulse">...</span>';
        btn.disabled = true;

        try {
            const response = await fetch(`${CONFIG.rotaShow}/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrfToken }
            });

            if (!response.ok) throw new Error('Erro ao buscar template');

            const data = await response.json();

            document.getElementById('modal-titulo').innerText = 'Editar: ' + data.nome_template;
            document.getElementById('template_id').value = data.id;
            document.getElementById('nome_template').value = data.nome_template; 
            document.getElementById('prompt_sistema').value = data.prompt_sistema;
            document.getElementById('json_schema_saida').value = data.json_schema_saida || '';

            toggleModal(true);

        } catch (error) {
            console.error(error);
            alert('Erro ao carregar dados. Tente recarregar a página.');
        } finally {
            btn.innerHTML = originalText; 
            btn.disabled = false;
        }
    }

    // --- IA GERADORA ---
    async function gerarAutomatico() {
        const exemplo = document.getElementById('exemplo_saida').value;
        const btn = document.getElementById('btn-gerar-automatico');
        const loading = document.getElementById('loading-ia');

        if (!exemplo.trim()) {
            alert('Por favor, cole um texto de exemplo primeiro.');
            document.getElementById('exemplo_saida').focus();
            return;
        }

        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        loading.classList.remove('hidden');

        try {
            const response = await fetch(CONFIG.rotaGerar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrfToken },
                body: JSON.stringify({ exemplo_saida: exemplo })
            });

            const data = await response.json();

            if (data.sucesso || data.success) {
                document.getElementById('prompt_sistema').value = data.prompt_sistema;
                document.getElementById('json_schema_saida').value = data.json_schema_saida;
                
                // Feedback visual suave
                const el = document.getElementById('prompt_sistema');
                el.classList.add('ring-2', 'ring-green-400', 'transition-all');
                setTimeout(() => el.classList.remove('ring-2', 'ring-green-400'), 1000);
            } else {
                alert('Erro na IA: ' + (data.erro || 'Falha desconhecida.'));
            }

        } catch (error) {
            console.error(error);
            alert('Erro de conexão com a IA.');
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            loading.classList.add('hidden');
        }
    }

    // --- SALVAR (CORREÇÃO DO REQUIRED) ---
    async function salvarTemplate() {
        const form = document.getElementById('form-template');
        
        // --- VALIDAÇÃO MANUAL DO HTML5 ---
        // Como o botão está "fora" (visualmente), chamamos a validação manualmente.
        if (!form.reportValidity()) {
            return; // Se houver campos inválidos (required), o navegador mostra o balão e para aqui.
        }

        const btnSalvar = document.getElementById('btn-salvar-template');
        const originalHtml = btnSalvar.innerHTML;
        
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Salvando...';

        let rawId = document.getElementById('template_id').value;
        const payload = {
            id: rawId ? rawId : null,
            nome_template: document.getElementById('nome_template').value, 
            prompt_sistema: document.getElementById('prompt_sistema').value,
            json_schema_saida: document.getElementById('json_schema_saida').value
        };

        try {
            const response = await fetch(CONFIG.rotaSalvar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrfToken },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && (data.success || data.sucesso)) {
                window.location.reload();
            } else {
                let msg = 'Erro ao salvar.';
                if (data.errors) msg = Object.values(data.errors).flat().join('\n');
                else if (data.message) msg = data.message;
                
                alert(msg);
            }
        } catch (error) {
            console.error(error);
            alert('Erro de conexão. Verifique o console.');
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = originalHtml;
        }
    }
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(event) {
        if(event.key === "Escape") fecharModal();
    });
</script>
@endsection