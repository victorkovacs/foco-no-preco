@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <style>
        .small-chart-container canvas { 
            max-width: 100px; 
            max-height: 100px; 
            margin: auto; 
        }
        .compact-card {
            transition: all 0.2s ease-in-out;
        }
        .compact-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <div class="mb-6">
            @include('components.system-health-widget')
        </div>

        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6">
            
            <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                <h1 class="text-xl font-bold text-gray-800 flex items-center">
                    <i data-lucide="bar-chart-2" class="w-6 h-6 mr-2 text-primary-dark"></i>
                    Visão Geral de Preços
                </h1>
                {{-- Total no Topo: Atualizados Hoje --}}
                <span class="text-xs text-blue-700 bg-blue-50 px-2 py-1 rounded border border-blue-100 font-medium">
                    {{ $total_pesquisados_hoje }} SKUs Atualizados Hoje
                </span>
            </div>

            @if ($connection_error)
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg mb-6 text-sm flex items-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                    <div>
                        <p class="font-bold">Atenção</p>
                        <p>{{ $connection_error }}</p>
                    </div>
                </div>
            @else
                
                {{-- SEÇÃO 1: TERMÔMETROS DE PREÇO --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'melhor']) }}" class="compact-card block bg-white border border-gray-200 rounded-lg p-4 hover:border-green-300 group cursor-pointer">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Melhor Preço</span>
                            <div class="p-1.5 bg-green-50 rounded-full group-hover:bg-green-100 text-green-600 transition-colors">
                                <i data-lucide="trending-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="flex items-end gap-2 mb-3">
                            <span id="thermo-melhor-value" class="text-2xl font-bold text-gray-800 leading-none">0</span>
                            <span class="text-xs text-gray-400 mb-0.5">produtos</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="thermo-melhor-fill" class="h-1.5 bg-green-500 rounded-full" style="width: 0%"></div>
                        </div>
                    </a>
                    
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'media']) }}" class="compact-card block bg-white border border-gray-200 rounded-lg p-4 hover:border-yellow-300 group cursor-pointer">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Na Média</span>
                            <div class="p-1.5 bg-yellow-50 rounded-full group-hover:bg-yellow-100 text-yellow-600 transition-colors">
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="flex items-end gap-2 mb-3">
                            <span id="thermo-media-value" class="text-2xl font-bold text-gray-800 leading-none">0</span>
                            <span class="text-xs text-gray-400 mb-0.5">produtos</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="thermo-media-fill" class="h-1.5 bg-yellow-400 rounded-full" style="width: 0%"></div>
                        </div>
                    </a>

                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'acima']) }}" class="compact-card block bg-white border border-gray-200 rounded-lg p-4 hover:border-red-300 group cursor-pointer">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Acima da Média</span>
                            <div class="p-1.5 bg-red-50 rounded-full group-hover:bg-red-100 text-red-600 transition-colors">
                                <i data-lucide="trending-up" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="flex items-end gap-2 mb-3">
                            <span id="thermo-acima-value" class="text-2xl font-bold text-gray-800 leading-none">0</span>
                            <span class="text-xs text-gray-400 mb-0.5">produtos</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div id="thermo-acima-fill" class="h-1.5 bg-red-500 rounded-full" style="width: 0%"></div>
                        </div>
                    </a>
                </div>

                <p class="text-xs text-gray-400 text-center mb-8">
                    Análise baseada nos {{ $total_pesquisados_hoje }} produtos com dados coletados hoje.
                </p>
                
                {{-- SEÇÃO 2: STATUS GERAIS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    
                    {{-- Card Concorrência --}}
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'sem_concorrencia']) }}" 
                       class="block border border-gray-100 bg-gray-50/50 rounded-lg p-4 hover:bg-white hover:border-gray-300 hover:shadow-sm transition-all group">
                        
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-sm font-semibold text-gray-700">Concorrência Detectada</h3>
                            <span class="text-xs font-mono text-gray-400" id="total-produtos-concorrencia">Total: 0</span>
                        </div>

                        <div class="flex items-center justify-between text-sm mb-2">
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full bg-primary-dark mr-2"></span>
                                <span class="text-gray-600">Com:</span>
                                <span class="ml-1 font-bold text-gray-800" id="val-com-concorrentes">0</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span>
                                <span class="text-gray-600">Sem:</span>
                                <span class="ml-1 font-bold text-gray-800" id="val-sem-concorrentes">0</span>
                            </div>
                        </div>

                        <div class="w-full h-2 bg-gray-200 rounded-full flex overflow-hidden">
                            <div id="thermo-concorrentes-fill-com" class="h-full bg-primary-dark transition-all duration-500" style="width: 0%"></div>
                            <div id="thermo-concorrentes-fill-sem" class="h-full bg-red-500 transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </a>

                    {{-- Card Status Pesquisa --}}
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'sem_pesquisa']) }}" 
                       class="block border border-gray-100 bg-gray-50/50 rounded-lg p-4 hover:bg-white hover:border-gray-300 hover:shadow-sm transition-all group">
                        
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-sm font-semibold text-gray-700">Atualização Hoje</h3>
                            <span class="text-xs font-mono text-gray-400" id="total-produtos-pesquisa">Total: 0</span>
                        </div>

                        <div class="flex items-center justify-between text-sm mb-2">
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full bg-blue-500 mr-2"></span>
                                <span class="text-gray-600">Atualizados:</span>
                                <span class="ml-1 font-bold text-gray-800" id="val-pesquisados">0</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full bg-gray-300 mr-2"></span>
                                <span class="text-gray-600">Pendentes:</span>
                                <span class="ml-1 font-bold text-gray-800" id="val-pendentes">0</span>
                            </div>
                        </div>

                        <div class="w-full h-2 bg-gray-200 rounded-full flex overflow-hidden">
                            <div id="thermo-pesquisa-fill-com" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                            <div id="thermo-pesquisa-fill-sem" class="h-full bg-gray-300 transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </a>
                </div>
                
                {{-- SEÇÃO 3: COMPETIDORES --}}
                <div>
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">
                        Top Concorrentes (Monitorados)
                    </h2>
                    
                    @if (empty($competidores_ativos_data))
                        <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <i data-lucide="users" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-500">Nenhum concorrente ativo encontrado.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            @foreach ($competidores_ativos_data as $index => $competidor)
                                <div class="bg-white border border-gray-100 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow text-center">
                                    <div class="small-chart-container mb-2">
                                        <canvas id="competidorChart-{{ $index }}"></canvas>
                                    </div>
                                    <h3 class="text-xs font-semibold text-gray-700 truncate w-full" title="{{ $competidor['nome'] }}">
                                        {{ $competidor['nome'] }}
                                    </h3>
                                    <p class="text-[10px] text-gray-400">{{ $competidor['count'] }} produtos</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            const competidoresData = {!! $chart_competidores_data_json !!};
            const totalComConcorrentes = {!! $total_com_concorrentes_json !!};
            const termometroPrecoData = {!! $termometro_preco_data_json !!};
            const termometroConcorrentesData = {!! $termometro_concorrentes_data_json !!};
            const statusPesquisaHojeData = {!! $status_pesquisa_hoje_json !!};

            if (termometroPrecoData) {
                const total = termometroPrecoData.total_monitorado || 1;
                ['melhor', 'media', 'acima'].forEach(key => {
                    const val = termometroPrecoData[key] || 0;
                    const percent = ((val / total) * 100).toFixed(1);
                    const elVal = document.getElementById(`thermo-${key}-value`);
                    const elFill = document.getElementById(`thermo-${key}-fill`);
                    if(elVal) elVal.textContent = val;
                    if(elFill) elFill.style.width = `${percent}%`;
                });
            }

            if (termometroConcorrentesData) {
                const com = termometroConcorrentesData.com;
                const sem = termometroConcorrentesData.sem;
                const total = termometroConcorrentesData.total || (com + sem);
                const pCom = total > 0 ? ((com / total) * 100).toFixed(1) : 0;
                const pSem = total > 0 ? ((sem / total) * 100).toFixed(1) : 0;

                document.getElementById('val-com-concorrentes').textContent = `${com} (${pCom}%)`;
                document.getElementById('val-sem-concorrentes').textContent = `${sem} (${pSem}%)`;
                document.getElementById('total-produtos-concorrencia').textContent = `Total: ${total}`;
                document.getElementById('thermo-concorrentes-fill-com').style.width = `${pCom}%`;
                document.getElementById('thermo-concorrentes-fill-sem').style.width = `${pSem}%`;
            }

            if (statusPesquisaHojeData) {
                const pesq = statusPesquisaHojeData.pesquisados_hoje;
                const sem = statusPesquisaHojeData.sem_pesquisa_hoje;
                const total = statusPesquisaHojeData.total || (pesq + sem);
                const pPesq = total > 0 ? ((pesq / total) * 100).toFixed(1) : 0;
                const pSem = total > 0 ? ((sem / total) * 100).toFixed(1) : 0;

                document.getElementById('val-pesquisados').textContent = `${pesq} (${pPesq}%)`;
                document.getElementById('val-pendentes').textContent = `${sem} (${pSem}%)`;
                document.getElementById('total-produtos-pesquisa').textContent = `Total: ${total}`;
                document.getElementById('thermo-pesquisa-fill-com').style.width = `${pPesq}%`;
                document.getElementById('thermo-pesquisa-fill-sem').style.width = `${pSem}%`;
            }

            if (competidoresData && totalComConcorrentes > 0) {
                competidoresData.forEach((competidor, index) => {
                    const ctx = document.getElementById(`competidorChart-${index}`);
                    if (ctx) {
                        const count = competidor.count;
                        const outros = totalComConcorrentes - count;
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: [competidor.nome, 'Outros'],
                                datasets: [{
                                    data: [count, outros < 0 ? 0 : outros],
                                    backgroundColor: ['#002D5A', '#F3F4F6'],
                                    borderWidth: 0,
                                    hoverOffset: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                                animation: { duration: 0 }
                            }
                        });
                    }
                });
            }
        });
    </script>
@endsection