@extends('layouts.app')

@section('title', 'Edição de Produtos')

@section('content')
<div class="w-full max-w-7xl mx-auto">
    
    {{-- Cabeçalho da Página --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <i data-lucide="edit-3" class="mr-3 text-primary-dark"></i>
                Edição dos Produtos
            </h1>
            <p class="text-gray-500 text-sm mt-1">Gerencie o catálogo, categorias e status.</p>
        </div>
        
        <a href="{{ route('produtos.mass_update') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg flex items-center transition-colors shadow-sm font-medium text-sm">
            <i data-lucide="layers" class="w-4 h-4 mr-2"></i>
            Atualização em Massa
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form method="GET" action="{{ route('produtos.gerenciar') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="pl-10 w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="SKU ou Nome...">
                </div>
            </div>
            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                <select name="filter_marca" onchange="this.form.submit()" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                    <option value="">Todas as Marcas</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca }}" {{ request('filter_marca') == $marca ? 'selected' : '' }}>{{ $marca }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3 flex gap-2">
                <a href="{{ route('produtos.gerenciar') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2.5 rounded-lg font-medium transition-colors text-sm flex-1 text-center">Limpar</a>
                <button type="submit" class="bg-primary-dark text-white px-4 py-2.5 rounded-lg font-medium hover:bg-opacity-90 transition-colors text-sm flex-1">Filtrar</button>
            </div>
        </form>
    </div>

    {{-- Tabela --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                        <th class="p-4 w-32">SKU</th>
                        <th class="p-4">Produto</th>
                        <th class="p-4 hidden md:table-cell">Categoria</th>
                        <th class="p-4 hidden md:table-cell">Marca</th>
                        <th class="p-4 text-center">Ativo</th>
                        <th class="p-4 w-24 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produtos as $produto)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="p-4 font-mono text-sm font-bold text-gray-700">{{ $produto->SKU }}</td>
                            <td class="p-4">
                                <div class="font-medium text-gray-800">{{ $produto->Nome }}</div>
                                @if($produto->LinkMeuSite)
                                    <a href="{{ $produto->LinkMeuSite }}" target="_blank" class="text-xs text-blue-500 hover:underline flex items-center mt-1">
                                        Link Site <i data-lucide="external-link" class="w-3 h-3 ml-1"></i>
                                    </a>
                                @endif
                            </td>
                            <td class="p-4 text-sm text-gray-600 hidden md:table-cell">{{ $produto->Categoria ?: '-' }}</td>
                            <td class="p-4 text-sm text-gray-600 hidden md:table-cell">
                                <span class="px-2 py-1 rounded bg-gray-100 text-xs border border-gray-200">{{ $produto->marca ?: 'N/D' }}</span>
                            </td>
                            <td class="p-4 text-center">
                                @if($produto->ativo)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">Sim</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">Não</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <button onclick="abrirModalEdicao({{ $produto->ID }})" class="p-1.5 hover:bg-blue-50 text-blue-600 rounded-lg transition-colors" title="Editar">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">{{ $produtos->links() }}</div>
    </div>
</div> 

{{-- CONTAINER DE TOASTS --}}
<div id="toast-container" class="fixed top-5 right-5 z-[70] space-y-4 pointer-events-none"></div>

{{-- MODAL DE EDIÇÃO --}}
<div id="modal-edicao" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity duration-300 opacity-0">
    <div class="bg-white w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl shadow-2xl transform scale-95 transition-transform duration-300 flex flex-col" id="modal-edicao-content">
        
        <div class="p-4 border-b border-gray-200 flex justify-between items-center sticky top-0 z-20 bg-white shadow-sm">
            <h3 class="text-xl font-bold text-gray-800">Editar Produto</h3>
            <button onclick="fecharModalEdicao()" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>

        <div id="modal-alert" class="hidden px-6 pt-4"></div>

        <form id="form-edicao" method="POST" class="p-6 pt-2">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                {{-- Info Principal --}}
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-4">
                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Produto</p>
                    <p id="produto-nome-edicao" class="font-medium text-gray-800 text-sm"></p>
                </div>

                <div class="flex gap-4">
                    <div class="w-1/3">
                        <label class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" id="sku_edicao" name="SKU" readonly class="mt-1 block w-full p-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500 cursor-not-allowed text-sm">
                    </div>
                    <div class="w-2/3">
                        <label class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" id="nome_edicao" name="Nome" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Marca</label>
                        <input type="text" id="marca_edicao" name="marca" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Categoria</label>
                        <input type="text" id="categoria_edicao" name="Categoria" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Link no meu site</label>
                    <input type="url" id="link_edicao" name="LinkMeuSite" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                {{-- MUDANÇA: Estrutura do Checkbox --}}
                <div class="pt-2 pb-2">
                    {{-- Fallback para quando o checkbox está desmarcado (envia 0) --}}
                    <input type="hidden" name="ativo" value="0">
                    <label class="inline-flex items-center cursor-pointer">
                        {{-- Checkbox (se marcado, envia 1) - Nome corrigido para 'ativo' --}}
                        <input type="checkbox" id="ativo_edicao" name="ativo" value="1" class="form-checkbox h-4 w-4 text-primary-dark rounded border-gray-300 focus:ring-primary-dark">
                        <span class="ml-2 text-sm text-gray-700 font-medium">Produto Ativo</span>
                    </label>
                </div>

                {{-- LISTA DE CONCORRENTES --}}
                <div class="mt-2">
                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center border-t pt-4">
                        <i data-lucide="users" class="w-4 h-4 mr-2 text-primary-dark"></i>
                        Concorrentes Monitorados
                    </h4>
                    <div id="alvos-container" class="space-y-2 bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <p id="alvos-status" class="text-xs text-gray-500">Carregando...</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white pb-2">
                <button type="button" onclick="fecharModalEdicao()" class="px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-sm">Cancelar</button>
                <button type="submit" id="btn-salvar-tudo" class="px-4 py-2 text-white bg-primary-dark rounded-lg hover:bg-opacity-90 font-medium text-sm">Salvar Tudo</button>
            </div>
        </form>
    </div>
</div>

<script>
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

    function fecharModalEdicao() {
        const modal = document.getElementById('modal-edicao');
        const content = document.getElementById('modal-edicao-content');
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); document.getElementById('modal-alert').classList.add('hidden'); modal.onclick = null; }, 300);
    }

    function verificarMudancaLink(idAlvo) {
        const inputElement = document.getElementById(`link_alvo_${idAlvo}`);
        const button = document.getElementById(`btn_salvar_alvo_${idAlvo}`);
        const novoLink = inputElement.value.trim();
        const linkOriginal = inputElement.dataset.originalLink.trim();

        if (novoLink !== linkOriginal) {
            button.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');
            button.classList.add('bg-green-600', 'text-white', 'border-green-600');
        } else {
            button.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
            button.classList.remove('bg-green-600', 'text-white', 'border-green-600');
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async function salvarLinkAlvo(idAlvo) {
        const inputElement = document.getElementById(`link_alvo_${idAlvo}`);
        const novoLink = inputElement.value.trim();
        const linkOriginal = inputElement.dataset.originalLink.trim();
        const button = document.getElementById(`btn_salvar_alvo_${idAlvo}`);

        if (novoLink === linkOriginal) { showToast('error', 'Nenhuma alteração detectada.'); return; }
        if (!inputElement.checkValidity()) { showToast('error', 'URL inválida.'); return; }

        button.disabled = true;
        button.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>';
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const updateUrl = "{{ url('produtos/alvo') }}/" + idAlvo + "/link";
        
        try {
            const response = await fetch(updateUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ novo_link: novoLink })
            });
            const result = await response.json();
            
            if (result.success) {
                showToast('success', 'Link atualizado com sucesso!');
                inputElement.dataset.originalLink = novoLink;
                verificarMudancaLink(idAlvo);
            } else {
                showToast('error', result.message);
            }
        } catch (e) {
            showToast('error', 'Erro de conexão ao salvar.');
        } finally {
            button.disabled = false;
            button.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    async function abrirModalEdicao(produtoId) {
        const modal = document.getElementById('modal-edicao');
        const content = document.getElementById('modal-edicao-content');
        const form = document.getElementById('form-edicao');
        const alvosContainer = document.getElementById('alvos-container');
        
        alvosContainer.innerHTML = '<p class="text-sm text-gray-500">Carregando...</p>';
        document.getElementById('modal-alert').classList.add('hidden');
        
        modal.classList.remove('hidden');
        setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); }, 10);
        modal.onclick = function(e) { if (e.target === modal) fecharModalEdicao(); };

        form.action = "{{ route('produtos.update', ':id') }}".replace(':id', produtoId);
        const fetchUrl = "{{ route('produtos.fetch', ':id') }}".replace(':id', produtoId);

        // Event Listener do Submit Principal
        form.onsubmit = async function(e) {
            e.preventDefault();
            const btnSalvar = document.getElementById('btn-salvar-tudo');
            const originalText = btnSalvar.innerHTML;
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin inline mr-2"></i> Salvando...';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST', // Laravel trata como PUT via _method
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                
                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        const errorMsg = Object.values(result.errors).flat()[0];
                        showToast('error', errorMsg);
                    } else {
                        showToast('error', result.message || 'Erro ao salvar.');
                    }
                } else {
                    showToast('success', 'Produto salvo com sucesso!');
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (err) {
                showToast('error', 'Erro de conexão.');
            } finally {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = originalText;
            }
        };

        try {
            const response = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (data.success) {
                const p = data.produto;
                document.getElementById('produto-nome-edicao').innerText = p.Nome;
                document.getElementById('sku_edicao').value = p.SKU;
                document.getElementById('nome_edicao').value = p.Nome;
                document.getElementById('marca_edicao').value = p.marca || '';
                document.getElementById('categoria_edicao').value = p.Categoria || '';
                document.getElementById('link_edicao').value = p.LinkMeuSite || '';
                
                // Lógica simplificada do Checkbox
                const chk = document.getElementById('ativo_edicao');
                chk.checked = p.ativo == 1;
                // Removemos a lógica JS de desabilitar o hidden pois agora usamos o padrão HTML

                alvosContainer.innerHTML = '';
                if (data.alvos && data.alvos.length > 0) {
                    data.alvos.forEach(alvo => {
                        const link = alvo.link || '';
                        const vendedor = alvo.NomeVendedor || 'Vendedor Desconhecido';
                        alvosContainer.innerHTML += `
                            <div class="bg-white border border-gray-200 rounded p-2 mb-2 text-sm hover:border-gray-300 transition-colors">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-semibold text-gray-700 text-xs flex items-center gap-1">
                                        <i data-lucide="shopping-bag" class="w-3 h-3 text-gray-400"></i> ${vendedor}
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <input type="url" id="link_alvo_${alvo.ID_Alvo}" value="${link}" data-original-link="${link}" 
                                           oninput="verificarMudancaLink(${alvo.ID_Alvo})"
                                           class="flex-1 p-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-primary-dark outline-none text-gray-600" 
                                           placeholder="https://...">
                                    <button type="button" id="btn_salvar_alvo_${alvo.ID_Alvo}" onclick="salvarLinkAlvo(${alvo.ID_Alvo})" 
                                            class="px-2 py-1 bg-white border border-gray-300 text-gray-600 rounded hover:bg-gray-50 text-xs flex items-center gap-1 transition-colors" 
                                            title="Salvar Link">
                                        <i data-lucide="save" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </div>`;
                    });
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                } else {
                    alvosContainer.innerHTML = '<p class="text-xs text-gray-400 text-center py-2">Nenhum concorrente vinculado.</p>';
                }
            } else {
                showToast('error', data.message);
            }
        } catch (e) {
            showToast('error', 'Erro ao carregar dados.');
        }
    }
    document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
</script>
@endsection