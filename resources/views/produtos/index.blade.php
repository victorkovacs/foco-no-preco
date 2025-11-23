@extends('layouts.app')

@section('title', 'Monitoramento de Produtos')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<div class="w-full max-w-7xl mx-auto">
    
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2 flex items-center">
        <i data-lucide="radar" class="mr-3 text-primary-dark"></i>
        Monitoramento de SKUs
    </h1>
    <p class="text-gray-600 mb-8">Gerencie e acompanhe os SKUs cadastrados no sistema.</p>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form method="GET" action="{{ route('produtos.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            
            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                <select name="filter_marca" onchange="this.form.submit()" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-900 outline-none text-sm">
                    <option value="">Todas as Marcas</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca }}" {{ request('filter_marca') == $marca ? 'selected' : '' }}>{{ $marca }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-900 outline-none text-sm" placeholder="SKU ou Nome...">
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="flex-1 bg-primary-dark text-white px-4 py-2.5 rounded-lg font-medium hover:bg-opacity-90 transition text-sm">Buscar</button>
                <a href="{{ route('produtos.index') }}" class="px-4 py-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition text-sm"><i data-lucide="x" class="w-5 h-5"></i></a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                        <th class="p-4">SKU</th>
                        <th class="p-4">Produto</th>
                        <th class="p-4 hidden md:table-cell">Marca</th>
                        <th class="p-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produtos as $produto)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4 font-mono text-sm font-bold text-gray-700">{{ $produto->SKU }}</td>
                            <td class="p-4">
                                <div class="font-medium text-gray-800">{{ $produto->Nome }}</div>
                                @if($produto->LinkMeuSite)
                                    <a href="{{ $produto->LinkMeuSite }}" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center mt-1">
                                        Ver Link <i data-lucide="external-link" class="w-3 h-3 ml-1"></i>
                                    </a>
                                @endif
                            </td>
                            <td class="p-4 text-sm text-gray-600 hidden md:table-cell">{{ $produto->marca }}</td>
                            <td class="p-4 text-center">
                                <button onclick="iniciarMonitoramento('{{ $produto->SKU }}', '{{ addslashes($produto->Nome) }}')" 
                                        class="bg-primary-dark text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-opacity-90 transition shadow-sm">
                                    Monitorar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200">{{ $produtos->links() }}</div>
    </div>
</div>

<div id="modal-monitoramento" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity duration-300 opacity-0">
    <div class="bg-white w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-xl shadow-2xl transform scale-95 transition-transform duration-300" id="modal-content">
        
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Detalhes do Monitoramento</h3>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>

        <div class="p-6">
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                <p class="text-sm font-medium text-blue-800"><strong id="product-code"></strong> - <span id="product-name"></span></p>
                <p class="text-xs text-blue-700 mt-1" id="date-range-display">Carregando dados...</p>
            </div>

            <div class="flex flex-wrap gap-4 items-end mb-6 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex-1 min-w-[150px]">
                    <label class="text-xs font-medium text-gray-600">Data Início</label>
                    <input type="date" id="data_inicio" class="w-full p-2 border rounded-lg text-sm">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="text-xs font-medium text-gray-600">Data Fim</label>
                    <input type="date" id="data_fim" class="w-full p-2 border rounded-lg text-sm">
                </div>
                <button onclick="aplicarFiltroData()" class="bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-90">Aplicar</button>
            </div>

            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-inner mb-6 h-80">
                <canvas id="priceChart"></canvas>
            </div>

            <h4 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-1">Última Atualização</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="p-2">Vendedor</th>
                            <th class="p-2">Preço</th>
                            <th class="p-2">Data</th>
                        </tr>
                    </thead>
                    <tbody id="concorrentes-tbody" class="divide-y divide-gray-100">
                        </tbody>
                </table>
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
            <button onclick="fecharModal()" class="px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100">Fechar</button>
        </div>
    </div>
</div>

<script>
    let priceChartInstance = null;
    let currentSKU = null;

    // Funções de Data Auxiliares
    const getFormattedDate = (date) => date.toISOString().split('T')[0];
    
    // Abre o Modal
    function iniciarMonitoramento(sku, nome) {
        const modal = document.getElementById('modal-monitoramento');
        const content = document.getElementById('modal-content');
        
        currentSKU = sku;
        document.getElementById('product-code').innerText = sku;
        document.getElementById('product-name').innerText = nome;

        // Datas Padrão (Últimos 7 dias)
        const hoje = new Date();
        const semanaPassada = new Date();
        semanaPassada.setDate(hoje.getDate() - 7);
        
        document.getElementById('data_fim').value = getFormattedDate(hoje);
        document.getElementById('data_inicio').value = getFormattedDate(semanaPassada);

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);

        carregarDadosGrafico(sku, getFormattedDate(semanaPassada), getFormattedDate(hoje));
    }

    function fecharModal() {
        const modal = document.getElementById('modal-monitoramento');
        const content = document.getElementById('modal-content');
        
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function aplicarFiltroData() {
        const ini = document.getElementById('data_inicio').value;
        const fim = document.getElementById('data_fim').value;
        if(currentSKU && ini && fim) carregarDadosGrafico(currentSKU, ini, fim);
    }

    // Busca dados do Laravel (Substitui a api_concorrentes.php)
    async function carregarDadosGrafico(sku, data_inicio, data_fim) {
        const tbody = document.getElementById('concorrentes-tbody');
        tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center">Carregando...</td></tr>';
        
        // URL da nova rota Laravel
        const url = `{{ route('produtos.grafico') }}?sku=${encodeURIComponent(sku)}&data_inicio=${data_inicio}&data_fim=${data_fim}`;

        try {
            const response = await fetch(url);
            const result = await response.json();
            
            if(result.success) {
                renderizarGrafico(result.data);
                renderizarTabela(result.data);
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-red-500">Erro ao buscar dados.</td></tr>';
            }
        } catch (error) {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-red-500">Erro de conexão.</td></tr>';
        }
    }

    function renderizarGrafico(data) {
        const ctx = document.getElementById('priceChart').getContext('2d');
        
        if (priceChartInstance) priceChartInstance.destroy();

        // Agrupa dados por vendedor
        const datasets = {};
        const labels = new Set(); // Datas únicas

        data.forEach(item => {
            if (!datasets[item.vendedor]) {
                datasets[item.vendedor] = {
                    label: item.vendedor,
                    data: [],
                    borderColor: getRandomColor(),
                    tension: 0.1
                };
            }
            // Formata data para YYYY-MM-DD para o eixo X
            const dataX = item.data_extracao.split(' ')[0];
            labels.add(dataX);
            
            datasets[item.vendedor].data.push({x: dataX, y: item.preco});
        });

        priceChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: Object.values(datasets)
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day' }
                    }
                }
            }
        });
    }

    function renderizarTabela(data) {
        const tbody = document.getElementById('concorrentes-tbody');
        // Filtra apenas os mais recentes (última data do array)
        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center">Sem dados neste período.</td></tr>';
            return;
        }
        
        // Ordena por data desc
        const sorted = [...data].sort((a,b) => new Date(b.data_extracao) - new Date(a.data_extracao));
        
        // Mostra os últimos 10 registos
        let html = '';
        sorted.slice(0, 10).forEach(item => {
            const preco = parseFloat(item.preco).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
            const dataF = new Date(item.data_extracao).toLocaleDateString('pt-BR');
            html += `<tr>
                <td class="p-2">${item.vendedor}</td>
                <td class="p-2 font-semibold">${preco}</td>
                <td class="p-2 text-gray-500 text-xs">${dataF}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }

    function getRandomColor() {
        const colors = ['#002D5A', '#D00000', '#2563EB', '#F59E0B', '#10B981', '#8B5CF6'];
        return colors[Math.floor(Math.random() * colors.length)];
    }
</script>
@endsection