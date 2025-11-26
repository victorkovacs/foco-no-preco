@extends('layouts.app')

@section('title', 'Monitoramento de Produtos')

@section('content')
{{-- Bibliotecas Gráficas e PDF --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

{{-- Estilo para ocultar o resumo da paginação --}}
<style>
    nav div.hidden.sm\:flex-1.sm\:flex.sm\:items-center.sm\:justify-between div:first-child p {
        display: none;
    }
</style>

<div class="w-full max-w-7xl mx-auto font-sans antialiased">
    
    {{-- Cabeçalho Padronizado --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <i data-lucide="radar" class="mr-3 text-primary-dark"></i>
                Monitoramento de SKUs
            </h1>
            <p class="text-gray-500 text-sm mt-1">Acompanhe os preços da concorrência.</p>
        </div>

        <a href="{{ route('export.concorrentes', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" 
        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
            <i data-lucide="file-spreadsheet" class="w-4 h-4 mr-2"></i> 
            Baixar Relatório ({{ now()->format('d/m') }})
        </a>

    </div>
    
    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form method="GET" action="{{ route('produtos.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            
            <div class="md:col-span-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Marca</label>
                <select name="filter_marca" onchange="this.form.submit()" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-900 outline-none text-sm bg-gray-50">
                    <option value="">Todas as Marcas</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca }}" {{ request('filter_marca') == $marca ? 'selected' : '' }}>{{ $marca }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="pl-10 w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-900 outline-none text-sm bg-gray-50" placeholder="SKU ou Nome...">
                </div>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <a href="{{ route('produtos.index') }}" class="px-4 py-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition text-sm font-medium flex-1 text-center">Limpar</a>
                <button type="submit" class="flex-1 bg-primary-dark text-white px-4 py-2.5 rounded-lg font-medium hover:bg-opacity-90 transition text-sm">Buscar</button>
            </div>
        </form>
    </div>


    {{-- Tabela --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-600 font-bold tracking-wider">
                        <th class="p-4 w-32 group cursor-pointer hover:bg-gray-100 transition-colors">
                            <a href="{{ route('produtos.index', array_merge(request()->query(), ['sort' => 'SKU', 'dir' => request('dir') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                SKU
                                <i data-lucide="arrow-up-down" class="w-3 h-3 ml-1 text-gray-400 group-hover:text-gray-600"></i>
                            </a>
                        </th>
                        <th class="p-4 group cursor-pointer hover:bg-gray-100 transition-colors">
                            <a href="{{ route('produtos.index', array_merge(request()->query(), ['sort' => 'Nome', 'dir' => request('dir') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                Produto
                                <i data-lucide="arrow-up-down" class="w-3 h-3 ml-1 text-gray-400 group-hover:text-gray-600"></i>
                            </a>
                        </th>
                        <th class="p-4 hidden md:table-cell group cursor-pointer hover:bg-gray-100 transition-colors">
                            <a href="{{ route('produtos.index', array_merge(request()->query(), ['sort' => 'marca', 'dir' => request('dir') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                Marca
                                <i data-lucide="arrow-up-down" class="w-3 h-3 ml-1 text-gray-400 group-hover:text-gray-600"></i>
                            </a>
                        </th>
                        <th class="p-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produtos as $produto)
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="p-4 font-mono text-sm font-semibold text-gray-700">{{ $produto->SKU }}</td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <span class="font-medium text-gray-800 text-sm">{{ $produto->Nome }}</span>
                                    @if($produto->LinkMeuSite)
                                        <a href="{{ $produto->LinkMeuSite }}" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors" title="Ver no site">
                                            <i data-lucide="external-link" class="w-4 h-4"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-sm text-gray-600 hidden md:table-cell">
                                <span class="px-2 py-1 rounded bg-gray-100 text-xs font-medium border border-gray-200">{{ $produto->marca }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <button onclick="iniciarMonitoramento('{{ $produto->SKU }}', '{{ addslashes($produto->Nome) }}')" 
                                        class="bg-white border border-blue-200 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-blue-50 transition shadow-sm flex items-center justify-center mx-auto gap-1">
                                    <i data-lucide="activity" class="w-3 h-3"></i> Monitorar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500 text-sm">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-center">
            {{ $produtos->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- MODAL DE DETALHES DO PRODUTO --}}
<div id="modal-monitoramento" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity duration-300 opacity-0">
    <div class="bg-white w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-xl shadow-2xl transform scale-95 transition-transform duration-300" id="modal-content">
        
        {{-- CABEÇALHO STICKY --}}
        <div class="p-6 border-b border-gray-200 flex flex-col gap-2 sticky top-0 z-20 bg-white shadow-md">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">Detalhes do Monitoramento</h3>
                <button onclick="fecharModal()" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-6 h-6"></i></button>
            </div>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded-lg -mx-2">
                <p class="text-sm font-medium text-blue-800"><strong id="product-code"></strong> - <span id="product-name"></span></p>
                <p class="text-xs text-blue-700 mt-1" id="date-range-display"></p>
            </div>
        </div>

        <div class="p-6">
            
            <div class="flex flex-wrap gap-4 items-end mb-6 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex-1 min-w-[150px]">
                    <label class="text-xs font-medium text-gray-600">Data Início</label>
                    <input type="date" id="data_inicio" class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="text-xs font-medium text-gray-600">Data Fim</label>
                    <input type="date" id="data_fim" class="w-full p-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <button onclick="aplicarFiltroData()" class="bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-90">Aplicar</button>
            </div>

            {{-- GRÁFICO --}}
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-inner mb-6 h-80">
                <canvas id="priceChart"></canvas>
            </div>

            {{-- TABELA DE PREÇOS --}}
            <h4 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-1">Última Atualização</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="tabela-concorrentes">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="p-2">Vendedor</th>
                            <th class="p-2">Preço</th>
                            <th class="p-2">Data</th>
                            <th class="p-2 text-center">Link</th>
                        </tr>
                    </thead>
                    <tbody id="concorrentes-tbody" class="divide-y divide-gray-100">
                        </tbody>
                </table>
            </div>
        </div>
        
        {{-- RODAPÉ COM BOTÃO PDF --}}
        <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
            <button onclick="exportarPDF()" class="px-4 py-2 text-white bg-red-600 border border-red-700 rounded-lg hover:bg-red-700 font-medium flex items-center shadow-sm text-sm">
                <i data-lucide="file-text" class="w-4 h-4 mr-2"></i> Exportar PDF
            </button>
            <button onclick="fecharModal()" class="px-4 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 text-sm">Fechar</button>
        </div>
    </div>
</div>

<script>
    let priceChartInstance = null;
    let currentSKU = null;

    // Funções de Data Auxiliares
    const getFormattedDate = (date) => date.toISOString().split('T')[0];
    
    function updateDateDisplay(data_inicio, data_fim) {
        const display = document.getElementById('date-range-display');
        const formatForDisplay = (dateString) => {
            if (!dateString) return 'N/A';
            const [year, month, day] = dateString.split('-');
            return `${day}/${month}/${year}`;
        };
        display.innerText = `Período de Monitoramento: ${formatForDisplay(data_inicio)} - ${formatForDisplay(data_fim)}`;
    }

    // Abre o Modal
    function iniciarMonitoramento(sku, nome) {
        const modal = document.getElementById('modal-monitoramento');
        const content = document.getElementById('modal-content');
        
        currentSKU = sku;
        document.getElementById('product-code').innerText = sku;
        document.getElementById('product-name').innerText = nome;

        const hoje = new Date();
        const semanaPassada = new Date();
        semanaPassada.setDate(hoje.getDate() - 7);
        
        const dataInicio = getFormattedDate(semanaPassada);
        const dataFim = getFormattedDate(hoje);
        
        document.getElementById('data_fim').value = dataFim;
        document.getElementById('data_inicio').value = dataInicio;
        
        updateDateDisplay(dataInicio, dataFim);

        // Listener para fechar ao clicar fora
        modal.onclick = function(event) {
            if (event.target === modal) {
                fecharModal();
            }
        };

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);

        carregarDadosGrafico(sku, dataInicio, dataFim);
    }

    function fecharModal() {
        const modal = document.getElementById('modal-monitoramento');
        const content = document.getElementById('modal-content');
        
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.onclick = null; 
        }, 300);
    }

    function aplicarFiltroData() {
        const ini = document.getElementById('data_inicio').value;
        const fim = document.getElementById('data_fim').value;
        
        if(currentSKU && ini && fim) {
            updateDateDisplay(ini, fim);
            carregarDadosGrafico(currentSKU, ini, fim);
        }
    }

    async function carregarDadosGrafico(sku, data_inicio, data_fim) {
        const tbody = document.getElementById('concorrentes-tbody');
        tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center">Carregando...</td></tr>'; 
        
        const url = `{{ route('produtos.grafico') }}?sku=${encodeURIComponent(sku)}&data_inicio=${data_inicio}&data_fim=${data_fim}`;

        try {
            const response = await fetch(url);
            const result = await response.json();
            
            if(result.success) {
                renderizarGrafico(result.data);
                renderizarTabela(result.data);
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-red-500">Erro ao buscar dados.</td></tr>';
            }
        } catch (error) {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-red-500">Erro de conexão.</td></tr>';
        }
    }

    function renderizarGrafico(data) {
        const ctx = document.getElementById('priceChart').getContext('2d');
        if (priceChartInstance) priceChartInstance.destroy();

        const datasets = {};
        const labels = new Set(); 

        data.forEach(item => {
            if (!datasets[item.vendedor]) {
                datasets[item.vendedor] = {
                    label: item.vendedor,
                    data: [],
                    borderColor: getRandomColor(),
                    tension: 0.1,
                    borderWidth: 2 
                };
            }
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
                    x: { type: 'time', time: { unit: 'day' } },
                    y: { ticks: { callback: function(value) { return 'R$ ' + value.toFixed(2).replace('.', ','); } } }
                },
                plugins: {
                    legend: {
                        position: 'bottom', 
                        labels: {
                            font: { size: 12 },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,      
                            boxHeight: 10,     
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderizarTabela(data) {
        const tbody = document.getElementById('concorrentes-tbody');
        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center">Sem dados neste período.</td></tr>';
            return;
        }
        
        // Ordena por data desc
        const sorted = [...data].sort((a,b) => new Date(b.data_extracao) - new Date(a.data_extracao));
        
        let html = '';
        sorted.slice(0, 10).forEach(item => {
            const preco = parseFloat(item.preco).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
            const dataF = new Date(item.data_extracao).toLocaleDateString('pt-BR');
            
            // Link corrigido usando 'link_concorrente' que vem do backend
            const link = item.link_concorrente || '#'; 
            const linkHtml = link !== '#' 
                ? `<a href="${link}" target="_blank" class="text-blue-600 hover:underline flex items-center justify-center">Ver <i data-lucide="external-link" class="w-3 h-3 ml-1"></i></a>` 
                : '<span class="text-gray-400 flex justify-center">-</span>';

            html += `<tr>
                <td class="p-2">${item.vendedor}</td>
                <td class="p-2 font-semibold">${preco}</td>
                <td class="p-2 text-gray-500 text-xs">${dataF}</td>
                <td class="p-2 text-center">${linkHtml}</td> 
            </tr>`;
        });
        tbody.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function getRandomColor() {
        const colors = ['#002D5A', '#D00000', '#2563EB', '#F59E0B', '#10B981', '#8B5CF6'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    // --- EXPORTAR PDF ---
    function exportarPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        let y = 10;
        
        // Cabeçalho
        const sku = document.getElementById('product-code').innerText;
        const nome = document.getElementById('product-name').innerText;
        const periodo = document.getElementById('date-range-display').innerText;

        doc.setFontSize(16);
        doc.text('Relatório de Monitoramento', 10, y);
        y += 7;
        doc.setFontSize(12);
        doc.text(`Produto: ${nome} (${sku})`, 10, y);
        y += 7;
        doc.setFontSize(10);
        doc.text(periodo, 10, y);
        y += 10;
        
        // Gráfico
        const canvas = document.getElementById('priceChart');
        if (canvas) {
            try {
                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 180; 
                const imgHeight = canvas.height * imgWidth / canvas.width;
                
                doc.text('Histórico de Preços', 10, y);
                y += 5;
                doc.addImage(imgData, 'PNG', 15, y, imgWidth, imgHeight);
                y += imgHeight + 10;
            } catch (e) {
                console.error("Erro gráfico PDF:", e);
            }
        }

        // Tabela
        doc.text('Últimas Atualizações de Preço', 10, y);
        y += 5;
        
        const tableBody = document.getElementById('concorrentes-tbody');
        const rows = tableBody.querySelectorAll('tr');
        
        const tableData = [];
        const tableHeaders = ['Vendedor', 'Preço', 'Data', 'Link'];
        const linksUrls = []; // Para armazenar URLs reais

        rows.forEach((row, index) => {
            const cells = row.querySelectorAll('td');
            // Garante que a linha tem células de dados
            if (cells.length >= 4) {
                const vendedor = cells[0].innerText.trim();
                const preco = cells[1].innerText.trim();
                const data = cells[2].innerText.trim();
                
                // Pega a URL limpa
                const linkEl = cells[3].querySelector('a');
                let linkText = '-';
                let linkUrl = null;
                
                if (linkEl) {
                    linkText = 'Acessar Site';
                    linkUrl = linkEl.href;
                }
                
                linksUrls.push(linkUrl); // Guarda URL correspondente à linha
                tableData.push([vendedor, preco, data, linkText]);
            }
        });
        
        if (tableData.length > 0) {
            doc.autoTable({
                startY: y,
                head: [tableHeaders],
                body: tableData,
                theme: 'striped',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [37, 99, 235] },
                columnStyles: {
                    0: { cellWidth: 70 },
                    1: { cellWidth: 40, halign: 'right' },
                    2: { cellWidth: 30, halign: 'center' },
                    3: { cellWidth: 40, halign: 'center', textColor: [37, 99, 235] } 
                },
                // Adiciona o link clicável no PDF
                didDrawCell: function(data) {
                    if (data.section === 'body' && data.column.index === 3) {
                        const url = linksUrls[data.row.index];
                        if (url) {
                            doc.link(data.cell.x, data.cell.y, data.cell.width, data.cell.height, { url: url });
                        }
                    }
                }
            });
        } else {
            doc.text('(Sem dados para exibir)', 10, y + 10);
        }

        doc.save(`Monitoramento_${sku}.pdf`);
    }
</script>
@endsection