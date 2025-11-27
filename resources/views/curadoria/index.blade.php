@extends('layouts.app')

@section('title', 'Curadoria de Pesquisas')

@section('content')
<style>
    /* --- CSS DE CONTENÇÃO E ROLAGEM (Estilo Excel) --- */
    
    /* 1. Container que segura a tabela e força a rolagem interna */
    .table-scroll-container {
        max-width: 75vw; /* Limita a largura a 75% da tela */
        width: 100%;
        overflow-x: auto; /* Barra de rolagem horizontal */
        overflow-y: hidden;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin: 0 auto; /* Centraliza na página */
        -webkit-overflow-scrolling: touch; /* Scroll suave no mobile */
    }

    #curadoria_table {
        border-collapse: separate; 
        border-spacing: 0;
        width: max-content; /* A tabela cresce horizontalmente o quanto precisar */
    }

    /* Células Gerais */
    #curadoria_table th, #curadoria_table td {
        padding: 0.75rem 1rem;
        white-space: nowrap; /* Texto em uma linha só */
        border-bottom: 1px solid #e5e7eb;
    }

    /* --- CABEÇALHO FIXO (TOPO) --- */
    #curadoria_table thead th {
        position: sticky;
        top: 0;
        z-index: 30; /* Fica acima do conteúdo */
        background-color: #f9fafb;
        border-bottom: 2px solid #d1d5db;
        height: 50px;
    }

    /* --- COLUNAS FIXAS (ESQUERDA) --- */
    
    /* Coluna 1: SKU */
    #curadoria_table th:nth-child(1),
    #curadoria_table td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 20; /* Acima das células que rolam */
        background-color: #ffffff;
        border-right: 1px solid #e5e7eb;
        width: 100px;
        min-width: 100px;
        max-width: 100px;
    }

    /* Coluna 2: Produto (Logo após SKU) */
    #curadoria_table th:nth-child(2),
    #curadoria_table td:nth-child(2) {
        position: sticky;
        left: 100px; /* Começa onde a coluna 1 termina */
        z-index: 20;
        background-color: #ffffff;
        border-right: 2px solid #d1d5db; /* Borda mais grossa para separar */
        width: 250px;
        min-width: 250px;
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis; /* "..." se o texto for longo */
    }

    /* Interseção (Canto Superior Esquerdo) */
    #curadoria_table thead th:nth-child(1),
    #curadoria_table thead th:nth-child(2) {
        z-index: 40; /* Fica acima de tudo */
        background-color: #f9fafb;
    }

    /* --- STATUS DOTS --- */
    .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .status-green { background-color: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
    .status-red { background-color: #ef4444; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2); }
    .status-gray { background-color: #e5e7eb; border: 1px solid #d1d5db; }

    /* Hover: Mantém o fundo branco nas colunas fixas */
    #curadoria_table tbody tr:hover td { background-color: #f3f4f6; }
    #curadoria_table tbody tr:hover td:nth-child(1),
    #curadoria_table tbody tr:hover td:nth-child(2) { background-color: #ffffff; }
</style>

<div class="w-full px-6 py-6">
    
    {{-- Título --}}
    <div class="flex justify-between items-end mb-6 max-w-[75vw] mx-auto">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Curadoria de Pesquisas</h1>
            <p class="text-lg text-gray-600">Verifique o status das pesquisas de produtos por concorrente.</p>
        </div>
    </div>

    {{-- Filtros (Layout Original) --}}
    <div class="flex flex-col gap-4 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-100 max-w-[75vw] mx-auto shadow-sm">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="w-full md:w-1/4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da Pesquisa</label>
                <input type="date" id="inputDate" class="w-full p-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="w-full md:w-3/4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Produto (SKU ou Nome)</label>
                <div class="flex">
                    <input type="text" id="inputSearch" placeholder="Digite o SKU ou nome..." class="w-full p-2.5 border border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm outline-none">
                    <button id="btnBuscar" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 rounded-r-lg font-semibold shadow-sm transition-colors duration-150 flex items-center">
                        Buscar
                    </button>
                </div>
            </div>
        </div>
        
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Vendedor</label>
                <select id="selectVendedor" class="w-full p-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="todos">Todos os Vendedores</option>
                </select>
            </div>
            
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Status</label>
                <select id="selectStatus" class="w-full p-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white disabled:bg-gray-100 disabled:text-gray-400" disabled>
                    <option value="todos">Todos os Status</option>
                    <option value="pesquisado">Pesquisado (Verde)</option>
                    <option value="nao_pesquisado">Não Pesquisado (Vermelho)</option>
                    <option value="sem_link">Sem Link (Cinza)</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Legenda --}}
    <div class="flex flex-wrap items-center space-x-6 mb-4 px-4 py-2 bg-white border border-gray-200 rounded-lg max-w-[75vw] mx-auto">
        <span class="text-sm font-bold text-gray-700 uppercase tracking-wide">Legenda:</span>
        <div class="flex items-center">
            <span class="status-dot status-green mr-2"></span>
            <span class="text-sm text-gray-600">Pesquisado (Sucesso)</span>
        </div>
        <div class="flex items-center">
            <span class="status-dot status-red mr-2"></span>
            <span class="text-sm text-gray-600">Link existe, mas não pesquisou hoje</span>
        </div>
        <div class="flex items-center">
            <span class="status-dot status-gray mr-2"></span>
            <span class="text-sm text-gray-600">Sem histórico/Link</span>
        </div>
    </div>

    {{-- Tabela (Scrollável) --}}
    <div class="table-scroll-container shadow-md">
        <table id="curadoria_table">
            <thead class="text-xs text-gray-500 font-bold uppercase tracking-wider">
                </thead>
            <tbody id="resultsBody" class="text-sm text-gray-700">
                <tr><td colspan="99" class="p-8 text-center text-gray-400">Carregando dados...</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    <div id="pagination" class="mt-6 flex justify-between items-center text-sm text-gray-600 max-w-[75vw] mx-auto"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const dom = {
            date: document.getElementById('inputDate'),
            search: document.getElementById('inputSearch'),
            vendor: document.getElementById('selectVendedor'),
            status: document.getElementById('selectStatus'),
            btn: document.getElementById('btnBuscar'),
            thead: document.querySelector('#curadoria_table thead'),
            tbody: document.getElementById('resultsBody'),
            pagination: document.getElementById('pagination')
        };

        let state = { page: 1 };

        // Função de Segurança Anti-XSS
        function escapeHtml(text) {
            if (!text) return text;
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Inicialização
        dom.date.value = new Date().toISOString().split('T')[0];

        // Event Listeners
        dom.btn.addEventListener('click', () => { state.page = 1; fetchData(); });
        dom.search.addEventListener('keydown', (e) => e.key === 'Enter' && (state.page=1, fetchData()));
        dom.date.addEventListener('change', () => { state.page = 1; fetchData(); });
        
        dom.vendor.addEventListener('change', () => {
            const isAll = dom.vendor.value === 'todos';
            dom.status.disabled = isAll;
            if (isAll) dom.status.value = 'todos';
            state.page = 1;
            fetchData();
        });
        
        dom.status.addEventListener('change', () => { state.page = 1; fetchData(); });

        // Carga inicial
        fetchData();

        async function fetchData() {
            dom.tbody.innerHTML = '<tr><td colspan="99" class="p-12 text-center text-gray-400">Atualizando...</td></tr>';

            const params = new URLSearchParams({
                data_pesquisa: dom.date.value,
                search: dom.search.value,
                filtro_vendedor_id: dom.vendor.value,
                filtro_status: dom.status.value,
                p: state.page
            });

            try {
                const res = await fetch(`{{ route('curadoria.search') }}?${params}`);
                const json = await res.json();

                if (json.success) {
                    // Preenche select de vendedores se estiver vazio
                    if (dom.vendor.children.length <= 1) {
                        let opts = '<option value="todos">Todos os Vendedores</option>';
                        json.data.vendedores.forEach(v => {
                            opts += `<option value="${escapeHtml(v.ID_Vendedor)}">${escapeHtml(v.NomeVendedor)}</option>`;
                        });
                        dom.vendor.innerHTML = opts;
                    }
                    renderTable(json.data.produtos, json.data.vendedores);
                    renderPagination(json.pagination);
                } else {
                    dom.tbody.innerHTML = `<tr><td colspan="99" class="p-8 text-center text-red-500 font-medium">Erro: ${escapeHtml(json.error)}</td></tr>`;
                }
            } catch (err) {
                console.error(err);
                dom.tbody.innerHTML = `<tr><td colspan="99" class="p-8 text-center text-red-500 font-medium">Erro de conexão.</td></tr>`;
            }
        }

        function renderTable(produtos, vendedores) {
            // 1. Renderizar Cabeçalho
            let headerHtml = `<tr>
                <th class="text-left">SKU</th>
                <th class="text-left">Produto</th>`;
            
            vendedores.forEach(v => {
                const nomeVend = escapeHtml(v.NomeVendedor);
                headerHtml += `<th class="text-center min-w-[100px] max-w-[150px] truncate" title="${nomeVend}">
                    ${nomeVend.substring(0, 12)}
                </th>`;
            });
            headerHtml += `</tr>`;
            dom.thead.innerHTML = headerHtml;

            // 2. Renderizar Corpo
            if (!produtos.length) {
                dom.tbody.innerHTML = `<tr><td colspan="99" class="p-12 text-center text-gray-500">Nenhum resultado encontrado.</td></tr>`;
                return;
            }

            let bodyHtml = '';
            produtos.forEach(p => {
                const skuSafe = escapeHtml(p.SKU);
                const nomeSafe = escapeHtml(p.Nome);

                bodyHtml += `<tr class="hover:bg-blue-50 transition-colors h-12">
                    <td class="font-medium text-gray-900 text-xs">${skuSafe}</td>
                    <td class="text-gray-600 text-xs truncate" title="${nomeSafe}">${nomeSafe}</td>`;
                
                vendedores.forEach(v => {
                    const status = p.status[v.ID_Vendedor];
                    let dotClass = 'status-gray';
                    let title = 'Sem Link';

                    if (status === 'pesquisado') { 
                        dotClass = 'status-green'; title = 'Pesquisado'; 
                    } else if (status === 'nao_pesquisado') { 
                        dotClass = 'status-red'; title = 'Link Existe (Sem Coleta)'; 
                    }

                    bodyHtml += `<td class="text-center p-0 border-l border-gray-50 align-middle">
                        <div class="w-full h-full flex items-center justify-center" title="${title}">
                            <span class="status-dot ${dotClass}"></span>
                        </div>
                    </td>`;
                });
                bodyHtml += `</tr>`;
            });
            dom.tbody.innerHTML = bodyHtml;
        }

        function renderPagination(pg) {
            dom.pagination.innerHTML = `
                <span class="font-medium">Página ${pg.currentPage} de ${pg.totalPages} <span class="text-gray-400">(${pg.totalRows} registros)</span></span>
                <div class="flex gap-2">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition" 
                        onclick="changePage(${pg.currentPage - 1})" ${pg.currentPage <= 1 ? 'disabled' : ''}>Anterior</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition" 
                        onclick="changePage(${pg.currentPage + 1})" ${pg.currentPage >= pg.totalPages ? 'disabled' : ''}>Próximo</button>
                </div>`;
        }

        window.changePage = (p) => { if(p>0) { state.page = p; fetchData(); } };
    });
</script>
@endsection