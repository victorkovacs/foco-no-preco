@extends('layouts.app')

@section('title', 'Gerenciador de Seletores')

@section('content')
{{-- ... (O HTML do Layout e Modal permanece igual ao anterior, sem alterações visuais necessárias) ... --}}
{{-- Apenas replique o HTML da resposta anterior se precisar, mas o foco aqui é o SCRIPT abaixo --}}

<div class="w-full max-w-7xl mx-auto bg-white shadow-xl rounded-2xl p-6 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Gerenciador de Seletores</h1>
            <p class="text-sm text-gray-500 mt-1">Configure como o robô deve ler os preços de cada concorrente.</p>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('concorrentes.index') }}" class="mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200 flex gap-4 items-end flex-wrap">
        <div class="flex-grow min-w-[200px]">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Buscar</label>
            <div class="relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome do concorrente..." class="w-full pl-9 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>
        </div>
        <div class="min-w-[150px]">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Status</label>
            <select name="filter_status" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                <option value="todos" {{ request('filter_status') == 'todos' ? 'selected' : '' }}>Todos</option>
                <option value="ativos" {{ request('filter_status') == 'ativos' ? 'selected' : '' }}>Ativos</option>
                <option value="inativos" {{ request('filter_status') == 'inativos' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <button type="submit" class="h-[42px] px-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center">Filtrar</button>
        @if(request()->hasAny(['search', 'filter_status']))
            <a href="{{ route('concorrentes.index') }}" class="h-[42px] px-4 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg text-sm font-medium transition-colors flex items-center">Limpar</a>
        @endif
    </form>

    {{-- Tabela --}}
    <div class="overflow-hidden border border-gray-200 rounded-xl shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-16">ID</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nome</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Seletor Técnico</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($vendedores as $vendedor)
                    <tr class="hover:bg-indigo-50/30 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $vendedor->ID_Vendedor }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ $vendedor->NomeVendedor }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <code class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600 font-mono border border-gray-200 block truncate max-w-[250px]" title="{{ $vendedor->SeletorPreco }}">
                                {{ $vendedor->SeletorPreco ?: 'Não configurado' }}
                            </code>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($vendedor->Ativo)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Ativo</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">Inativo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="abrirModalEditar({{ json_encode($vendedor) }})" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm inline-flex items-center transition-transform hover:scale-105">
                                <i data-lucide="settings-2" class="w-4 h-4 mr-1.5"></i> Configurar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500"><div class="flex flex-col items-center justify-center"><i data-lucide="search-x" class="w-10 h-10 text-gray-300 mb-3"></i><p>Nenhum concorrente encontrado.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $vendedores->links() }}</div>
</div>

<div id="toast-container" class="fixed top-5 right-5 z-[80] space-y-4 pointer-events-none"></div>

{{-- MODAL --}}
<div id="modal-editar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 transition-opacity duration-300 opacity-0" aria-modal="true" role="dialog">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="modal-content">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white rounded-t-2xl">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2"><i data-lucide="settings" class="w-5 h-5 text-indigo-600"></i> Configurar Concorrente</h3>
                <p class="text-sm text-gray-500 mt-0.5">Ajuste os parâmetros de extração do robô.</p>
            </div>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="overflow-y-auto p-6 custom-scrollbar">
            <form id="form-editar" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="space-y-5">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2 mb-4">Dados Cadastrais</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Vendedor</label>
                            <div class="relative">
                                <i data-lucide="store" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                <input type="text" id="edit_nome" name="NomeVendedor" readonly class="w-full pl-10 p-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 font-medium cursor-lock">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Link do Site</label>
                            <div class="relative">
                                <i data-lucide="link" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                <input type="url" id="edit_link_concorrente" name="LinkConcorrente" class="w-full pl-10 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="https://site-do-concorrente.com">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="edit_ativo" name="Ativo" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Desconto (%)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" id="edit_desconto" name="PercentualDescontoAVista" class="w-full p-2.5 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 placeholder-gray-400" placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-5">
                        <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-gray-100 pb-2 mb-4">Configuração do Robô</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center justify-between">Seletor de Preço (CSS/XPath) <span class="text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded cursor-help" title="Caminho no HTML para encontrar o preço">Obrigatório</span></label>
                            <input type="text" id="edit_seletor" name="SeletorPreco" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono text-sm text-gray-700" placeholder=".product-price-amount">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filtro Produtos</label>
                            <input type="text" id="edit_filtro" name="FiltroLinkProduto" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 font-mono text-sm text-gray-700" placeholder="/[^0-9,]/g">
                            <p class="text-[10px] text-gray-400 mt-1">Use para encontrar os links dos produtos no sitemap.</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                    <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2"><i data-lucide="test-tube-2" class="w-4 h-4 text-indigo-500"></i> Área de Teste (Seletor de Preço)</h4>
                    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
                        <div class="w-full">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Link de Produto (Exemplo para teste)</label>
                            <div class="relative">
                                <i data-lucide="link-2" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                <input type="url" id="edit_link_teste" class="w-full pl-10 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Cole aqui o link de um produto específico para testar o seletor">
                            </div>
                        </div>
                        <button type="button" onclick="testarSeletor()" class="w-full sm:w-auto h-[42px] px-6 bg-gray-900 hover:bg-black text-white rounded-lg font-medium text-sm transition-all shadow-md flex items-center justify-center gap-2 active:scale-95"><i data-lucide="play" class="w-4 h-4"></i> Testar</button>
                    </div>
                    <div id="teste-resultado" class="hidden mt-4 bg-white border border-gray-200 rounded-lg p-4 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-gray-300" id="teste-status-bar"></div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Resultado da Extração</p>
                        <div id="teste-conteudo" class="space-y-2 font-mono text-sm text-gray-700"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-2xl">
            <button type="button" onclick="fecharModal()" class="px-5 py-2.5 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-sm transition-colors">Cancelar</button>
            <button type="button" onclick="document.getElementById('form-editar').dispatchEvent(new Event('submit'))" id="btn-salvar" class="px-5 py-2.5 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 font-medium text-sm shadow-md transition-all flex items-center">Salvar Alterações</button>
        </div>
    </div>
</div>

<style>.custom-scrollbar::-webkit-scrollbar { width: 6px; } .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; } .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; } .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }</style>

<script>
    function showToast(type, message) {
        const container = document.getElementById('toast-container');
        const bgClass = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
        const icon = type === 'success' ? 'check-circle' : 'alert-triangle';
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center p-4 mb-4 rounded-lg border shadow-lg transform transition-all duration-500 translate-x-full ${bgClass}`;
        toast.innerHTML = `<i data-lucide="${icon}" class="w-5 h-5 mr-3 flex-shrink-0"></i><div class="text-sm font-medium">${message}</div>`;
        container.appendChild(toast);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        requestAnimationFrame(() => toast.classList.remove('translate-x-full'));
        setTimeout(() => { toast.classList.add('translate-x-full', 'opacity-0'); setTimeout(() => toast.remove(), 300); }, 4000);
    }

    function abrirModalEditar(vendedor) {
        const modal = document.getElementById('modal-editar');
        const content = document.getElementById('modal-content');
        const form = document.getElementById('form-editar');
        
        document.getElementById('teste-resultado').classList.add('hidden');
        document.getElementById('teste-conteudo').innerHTML = '';

        document.getElementById('edit_nome').value = vendedor.NomeVendedor;
        document.getElementById('edit_seletor').value = vendedor.SeletorPreco || '';
        document.getElementById('edit_ativo').value = vendedor.Ativo;
        document.getElementById('edit_desconto').value = vendedor.PercentualDescontoAVista || '';
        document.getElementById('edit_filtro').value = vendedor.FiltroLinkProduto || '';
        document.getElementById('edit_link_concorrente').value = vendedor.LinkConcorrente || '';
        
        // Link de teste
        const linkTeste = vendedor.link_exemplo || vendedor.LinkConcorrente || '';
        document.getElementById('edit_link_teste').value = linkTeste;

        form.action = "{{ url('concorrentes') }}/" + vendedor.ID_Vendedor; 

        form.onsubmit = async function(e) {
            e.preventDefault();
            const btnSalvar = document.getElementById('btn-salvar');
            const originalHTML = btnSalvar.innerHTML;
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin mr-2"></i> Salvando...';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST', 
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast('success', result.message);
                    fecharModal();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast('error', result.message || 'Erro ao atualizar.');
                }
            } catch (error) {
                console.error(error);
                showToast('error', 'Erro de conexão.');
            } finally {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = originalHTML;
            }
        };

        modal.classList.remove('hidden');
        setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); }, 10);
        modal.onclick = function(e) { if (e.target === modal) fecharModal(); }
    }

    function fecharModal() {
        const modal = document.getElementById('modal-editar');
        const content = document.getElementById('modal-content');
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); modal.onclick = null; }, 300);
    }

    async function testarSeletor() {
        const link = document.getElementById('edit_link_teste').value;
        const seletor = document.getElementById('edit_seletor').value;
        const desconto = document.getElementById('edit_desconto').value; // [CORREÇÃO] Captura o desconto
        
        const resultadoDiv = document.getElementById('teste-resultado');
        const conteudoDiv = document.getElementById('teste-conteudo');
        const statusBar = document.getElementById('teste-status-bar');

        if (!link) {
            showToast('error', 'Por favor, insira um Link de Produto para o teste.');
            document.getElementById('edit_link_teste').focus();
            return;
        }

        resultadoDiv.classList.remove('hidden');
        statusBar.className = 'absolute top-0 left-0 w-1 h-full bg-indigo-500 animate-pulse';
        conteudoDiv.innerHTML = `<div class="flex items-center text-indigo-600"><i data-lucide="loader" class="w-5 h-5 animate-spin mr-3"></i><span>Conectando ao site e extraindo dados...</span></div>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            const response = await fetch("{{ route('concorrentes.testar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    url: link,
                    seletor_preco: seletor,
                    desconto: desconto // [CORREÇÃO] Envia o desconto
                })
            });

            const data = await response.json();

            if (data.success) {
                statusBar.className = 'absolute top-0 left-0 w-1 h-full bg-green-500';
                
                let avisoDesconto = desconto > 0 
                    ? `<span class="text-[10px] text-green-600 ml-1 font-bold">(-${desconto}%)</span>` 
                    : '';

                let html = `
                    <div class="flex items-center mb-2"><div class="bg-green-100 p-1.5 rounded-full mr-2"><i data-lucide="check" class="w-4 h-4 text-green-600"></i></div><span class="text-green-700 font-bold">Sucesso! Dados encontrados.</span></div>
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div class="bg-gray-50 p-2 rounded border border-gray-100"><span class="block text-[10px] text-gray-400 uppercase">Preço Final ${avisoDesconto}</span><span class="text-lg font-bold text-gray-800">R$ ${data.preco_final || '---'}</span></div>
                        <div class="bg-gray-50 p-2 rounded border border-gray-100"><span class="block text-[10px] text-gray-400 uppercase">Dado Bruto</span><span class="text-sm font-mono text-gray-600 truncate" title="${data.preco_bruto}">${data.preco_bruto || '---'}</span></div>
                    </div>`;
                conteudoDiv.innerHTML = html;
            } else {
                statusBar.className = 'absolute top-0 left-0 w-1 h-full bg-red-500';
                let html = `<div class="flex items-center mb-2"><div class="bg-red-100 p-1.5 rounded-full mr-2"><i data-lucide="x" class="w-4 h-4 text-red-600"></i></div><span class="text-red-700 font-bold">Falha na extração</span></div><div class="text-red-600 bg-red-50 p-3 rounded text-xs font-mono border border-red-100">${data.error_message || data.message || 'Erro desconhecido'}</div>`;
                if (data.python_error_details) {
                    html += `<div class="mt-2"><button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" class="text-xs text-gray-400 hover:text-gray-600 flex items-center underline">Ver detalhes técnicos</button><div class="hidden mt-1 p-2 bg-gray-900 text-gray-300 text-[10px] rounded font-mono overflow-x-auto">${data.python_error_details}</div></div>`;
                }
                conteudoDiv.innerHTML = html;
            }
        } catch (error) {
            console.error(error);
            statusBar.className = 'absolute top-0 left-0 w-1 h-full bg-red-500';
            conteudoDiv.innerHTML = `<div class="flex items-center text-red-600"><i data-lucide="wifi-off" class="w-5 h-5 mr-2"></i><span>Erro de comunicação com o servidor. Tente novamente.</span></div>`;
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    document.addEventListener('DOMContentLoaded', () => { if(typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection