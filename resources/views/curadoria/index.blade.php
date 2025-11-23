@extends('layouts.app')

@section('title', 'Curadoria de Pesquisas')

@section('content')
<div class="w-full max-w-7xl mx-auto p-6">
    
    <h1 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <i data-lucide="check-square" class="mr-3 text-primary-dark"></i>
        Curadoria de Pesquisas
    </h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da Verificação</label>
                <input type="date" id="inputDate" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
                <select id="selectVendedor" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white">
                    <option value="todos">Todos os Vendedores</option>
                    @foreach($vendedores as $vendedor)
                        <option value="{{ $vendedor->ID_Vendedor }}">{{ $vendedor->NomeVendedor }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="selectStatus" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white" disabled>
                    <option value="todos">Todos</option>
                    <option value="OK">OK (Sucesso)</option>
                    <option value="ERRO">Erro</option>
                    <option value="PENDENTE">Pendente</option>
                </select>
            </div>

            <div>
                <button id="btnBuscar" class="w-full bg-primary-dark hover:bg-primary-darker text-white px-4 py-2.5 rounded-lg font-medium transition-colors flex justify-center items-center">
                    <i data-lucide="search" class="w-4 h-4 mr-2"></i> Buscar
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                    <tr>
                        <th class="p-4">Produto</th>
                        <th class="p-4">Vendedor</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Última Verificação</th>
                        <th class="p-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody id="resultsBody" class="divide-y divide-gray-100">
                    <tr><td colspan="5" class="p-8 text-center text-gray-500">Selecione os filtros e clique em buscar.</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-gray-200 flex justify-between items-center bg-gray-50" id="paginationControls">
            <button id="prevPage" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" disabled>Anterior</button>
            <span class="text-sm text-gray-600">Página <span id="currentPage">1</span></span>
            <button id="nextPage" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" disabled>Próximo</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const inputDate = document.getElementById('inputDate');
        const selectVendedor = document.getElementById('selectVendedor');
        const selectStatus = document.getElementById('selectStatus');
        const btnBuscar = document.getElementById('btnBuscar');
        const resultsBody = document.getElementById('resultsBody');
        
        let currentPage = 1;

        // Configura data de hoje como padrão
        inputDate.valueAsDate = new Date();

        // Habilita/Desabilita status baseado no vendedor
        selectVendedor.addEventListener('change', () => {
            if (selectVendedor.value === 'todos') {
                selectStatus.value = 'todos';
                selectStatus.disabled = true;
            } else {
                selectStatus.disabled = false;
            }
        });

        // Buscar Dados
        btnBuscar.addEventListener('click', () => {
            currentPage = 1;
            fetchData();
        });

        // Paginação
        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchData();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            currentPage++;
            fetchData();
        });

        function fetchData() {
            resultsBody.innerHTML = '<tr><td colspan="5" class="p-8 text-center"><i data-lucide="loader-2" class="animate-spin mx-auto w-6 h-6 text-primary-dark"></i></td></tr>';
            lucide.createIcons();

            const params = new URLSearchParams({
                date: inputDate.value,
                vendedor: selectVendedor.value,
                status: selectStatus.value,
                page: currentPage
            });

            fetch(`{{ route('curadoria.search') }}?${params}`)
                .then(response => response.json())
                .then(json => {
                    renderTable(json.data);
                    updatePagination(json);
                })
                .catch(err => {
                    console.error(err);
                    resultsBody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-red-500">Erro ao carregar dados.</td></tr>';
                });
        }

        function renderTable(data) {
            if (data.length === 0) {
                resultsBody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-500">Nenhum registo encontrado.</td></tr>';
                return;
            }

            let html = '';
            data.forEach(item => {
                // Define cor do status
                let statusClass = 'bg-gray-100 text-gray-800';
                let statusIcon = 'minus';
                
                if (item.status_verificacao === 'OK') {
                    statusClass = 'bg-green-100 text-green-800';
                    statusIcon = 'check';
                } else if (item.status_verificacao === 'ERRO') {
                    statusClass = 'bg-red-100 text-red-800';
                    statusIcon = 'alert-circle';
                }

                const nomeProduto = item.produto ? item.produto.Nome : 'Produto Desconhecido';
                const nomeVendedor = item.vendedor ? item.vendedor.NomeVendedor : 'Link Externo';
                const dataVerif = new Date(item.data_ultima_verificacao).toLocaleString('pt-PT');

                html += `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4">
                            <div class="font-medium text-gray-900">${nomeProduto}</div>
                            <div class="text-xs text-gray-500 font-mono">${item.produto ? item.produto.SKU : '-'}</div>
                        </td>
                        <td class="p-4 text-sm text-gray-600">${nomeVendedor}</td>
                        <td class="p-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                                ${item.status_verificacao}
                            </span>
                        </td>
                        <td class="p-4 text-right text-sm text-gray-500">${dataVerif}</td>
                        <td class="p-4 text-center">
                            <button class="text-gray-400 hover:text-primary-dark transition-colors" title="Ver Detalhes">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            resultsBody.innerHTML = html;
            lucide.createIcons(); // Reinicia ícones nos novos elementos
        }

        function updatePagination(json) {
            document.getElementById('currentPage').innerText = json.current_page;
            document.getElementById('prevPage').disabled = json.current_page <= 1;
            document.getElementById('nextPage').disabled = json.current_page >= json.last_page;
        }

        // Carrega dados iniciais
        fetchData();
    });
</script>
@endsection