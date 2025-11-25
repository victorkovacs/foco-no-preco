@extends('layouts.app')

@section('title', 'Gerenciador de Seletores')

@section('content')
<div class="w-full max-w-7xl mx-auto bg-white shadow-xl rounded-2xl p-6 md:p-8">

    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Gerenciador de Seletores</h1>
    <p class="text-base text-gray-600 mb-6">
        Adicione ou edite os seletores, descontos e status de cada concorrente.
    </p>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('concorrentes.index') }}" class="mb-4 flex gap-3 items-center flex-wrap">
        <div class="flex-shrink-0">
            <select name="filter_status" onchange="this.form.submit()" class="p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm">
                <option value="todos" {{ request('filter_status') == 'todos' ? 'selected' : '' }}>Todos Status</option>
                <option value="ativos" {{ request('filter_status') == 'ativos' ? 'selected' : '' }}>Ativos</option>
                <option value="inativos" {{ request('filter_status') == 'inativos' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>

        <input type="text" name="search" value="{{ request('search') }}" placeholder="Pesquisar nome..." class="flex-grow p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm">
        
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
            Filtrar
        </button>
        
        @if(request()->hasAny(['search', 'filter_status']))
            <a href="{{ route('concorrentes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Limpar</a>
        @endif
    </form>

    {{-- Tabela --}}
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seletor Preço</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Desconto (%)</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filtro (Regex)</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($vendedores as $vendedor)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $vendedor->ID_Vendedor }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $vendedor->NomeVendedor }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs" title="{{ $vendedor->SeletorPreco }}">
                            {{ Str::limit($vendedor->SeletorPreco, 25) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-600">
                            {{ $vendedor->PercentualDescontoAVista ? $vendedor->PercentualDescontoAVista . '%' : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs" title="{{ $vendedor->FiltroLinkProduto }}">
                            {{ $vendedor->FiltroLinkProduto ? Str::limit($vendedor->FiltroLinkProduto, 20) : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($vendedor->Ativo)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inativo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="abrirModalEditar({{ json_encode($vendedor) }})" class="text-indigo-600 hover:text-indigo-900 flex items-center justify-end gap-1 ml-auto">
                                <i data-lucide="edit" class="w-4 h-4"></i> Editar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500">Nenhum concorrente encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $vendedores->links() }}</div>
</div>

{{-- CONTAINER DE TOASTS --}}
<div id="toast-container" class="fixed top-5 right-5 z-[70] space-y-4 pointer-events-none"></div>

{{-- MODAL DE EDIÇÃO --}}
<div id="modal-editar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity duration-300 opacity-0">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto" id="modal-content">
        
        <div class="p-5 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
            <h3 class="text-lg font-bold text-gray-800">Editar Concorrente</h3>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>

        <form id="form-editar" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                {{-- Nome --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Vendedor</label>
                    <input type="text" id="edit_nome" name="NomeVendedor" required 
                           class="w-full p-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed" readonly>
                </div>

                {{-- Seletor de Preço --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Seletor de Preço (CSS/XPath)</label>
                    <input type="text" id="edit_seletor" name="SeletorPreco" 
                           class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <p class="text-xs text-gray-500 mt-1">Ex: .price-tag ou #product-price</p>
                </div>

                {{-- Campos Extras --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Desconto (%)</label>
                        <input type="number" step="0.01" id="edit_desconto" name="PercentualDescontoAVista" 
                               class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="edit_ativo" name="Ativo" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filtro (Regex/Texto)</label>
                    <input type="text" id="edit_filtro" name="FiltroLinkProduto" 
                           class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <p class="text-xs text-gray-500 mt-1">Expressão regular para limpar o preço.</p>
                </div>

                {{-- Campo Link --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link de Exemplo</label>
                    <input type="url" id="edit_link" name="LinkConcorrente" 
                           class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="https://...">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="fecharModal()" class="px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-sm">Cancelar</button>
                <button type="submit" id="btn-salvar" class="px-4 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 font-medium text-sm shadow-sm">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- TOAST SYSTEM ---
    function showToast(type, message) {
        const container = document.getElementById('toast-container');
        const bgClass = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
        const icon = type === 'success' ? 'check-circle' : 'alert-triangle';
        
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center p-4 mb-4 rounded-lg border shadow-lg transform transition-all duration-300 translate-x-full ${bgClass}`;
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

        // Preenche os campos
        document.getElementById('edit_nome').value = vendedor.NomeVendedor;
        document.getElementById('edit_seletor').value = vendedor.SeletorPreco || '';
        document.getElementById('edit_ativo').value = vendedor.Ativo;
        document.getElementById('edit_desconto').value = vendedor.PercentualDescontoAVista || '';
        document.getElementById('edit_filtro').value = vendedor.FiltroLinkProduto || '';
        document.getElementById('edit_link').value = vendedor.LinkConcorrente || '';

        // Configura a ação do formulário
        form.action = "{{ url('concorrentes') }}/" + vendedor.ID_Vendedor; 

        // Event listener para o submit via AJAX
        form.onsubmit = async function(e) {
            e.preventDefault();
            const btnSalvar = document.getElementById('btn-salvar');
            const originalText = btnSalvar.innerHTML;
            
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin inline mr-2"></i> Salvando...';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                const formData = new FormData(form);
                // Laravel espera PUT mas FormData envia como POST com _method
                // O HTML já tem @method('PUT'), então o FormData já inclui _method=PUT
                
                const response = await fetch(form.action, {
                    method: 'POST', 
                    headers: {
                        'Accept': 'application/json', // Importante para o Controller retornar JSON
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast('success', result.message);
                    fecharModal();
                    setTimeout(() => window.location.reload(), 1000); // Recarrega para atualizar a tabela
                } else {
                    showToast('error', result.message || 'Erro ao atualizar.');
                }
            } catch (error) {
                console.error(error);
                showToast('error', 'Erro de conexão.');
            } finally {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = originalText;
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

    document.addEventListener('DOMContentLoaded', () => { if(typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection