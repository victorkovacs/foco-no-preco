@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        @include('components.system-health-widget')

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                {{ __("You're logged in!") }}
            </div>
        </div>
        
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Dashboard de Concorrência')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <style>
        .small-chart-container canvas { max-width: 150px; max-height: 150px; margin: auto; }
        .thermometer-container { border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; background-color: #f9fafb; text-align: center; }
        .thermometer-label { font-size: 0.875rem; font-weight: 500; color: #4b5563; margin-bottom: 0.5rem; }
        .thermometer-value { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin-bottom: 0.75rem; }
        .thermometer-bar-bg { background-color: #e5e7eb; border-radius: 9999px; height: 0.75rem; overflow: hidden; display: flex; }
        .thermometer-bar-fill { height: 100%; transition: width 0.5s ease-in-out; }
        #thermo-concorrentes-fill-com { border-radius: 9999px 0 0 9999px; }
        #thermo-concorrentes-fill-sem { border-radius: 0 9999px 9999px 0; }
        .thermometer-bar-bg.single-fill #thermo-concorrentes-fill-com,
        .thermometer-bar-bg.single-fill #thermo-concorrentes-fill-sem { border-radius: 9999px; }
    </style>

    <div class="w-full max-w-6xl mx-auto bg-white shadow-xl rounded-2xl p-8 md:p-10">
        
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-8 text-center">
            Dashboard de Concorrência
        </h1>

        @if ($connection_error)
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md mb-6" role="alert">
                <p class="font-bold">Aviso</p>
                <p>{{ $connection_error }}</p>
            </div>
        @else
            
            <div class="mb-10 pb-8 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-700 mb-6 text-center">Posicionamento de Preço (Extração de Hoje)</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'melhor']) }}" class="thermometer-container block hover:shadow-lg hover:bg-gray-100 transition-all duration-200">
                        <div class="thermometer-label">Melhor Preço</div>
                        <div id="thermo-melhor-value" class="thermometer-value">0 / 0</div>
                        <div class="thermometer-bar-bg">
                            <div id="thermo-melhor-fill" class="thermometer-bar-fill bg-green-500" style="width: 0%;"></div>
                        </div>
                    </a>
                    
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'media']) }}" class="thermometer-container block hover:shadow-lg hover:bg-gray-100 transition-all duration-200">
                        <div class="thermometer-label">Na Média</div>
                        <div id="thermo-media-value" class="thermometer-value">0 / 0</div>
                        <div class="thermometer-bar-bg">
                            <div id="thermo-media-fill" class="thermometer-bar-fill bg-yellow-400" style="width: 0%;"></div>
                        </div>
                    </a>

                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'acima']) }}" class="thermometer-container block hover:shadow-lg hover:bg-gray-100 transition-all duration-200">
                        <div class="thermometer-label">Acima da Média</div>
                        <div id="thermo-acima-value" class="thermometer-value">0 / 0</div>
                        <div class="thermometer-bar-bg">
                            <div id="thermo-acima-fill" class="thermometer-bar-fill bg-red-500" style="width: 0%;"></div>
                        </div>
                    </a>
                </div>
                <p class="text-xs text-gray-500 text-center mt-3">
                    Comparação baseada nos preços de HOJE para {{ $total_skus_monitorados_preco }} SKUs únicos monitorados.
                </p>
            </div>
            
            <div class="flex flex-wrap md:flex-nowrap gap-8 mb-8 pb-8 border-b border-gray-200">
                 <div class="w-full md:w-1/2 flex flex-col items-center">
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'sem_concorrencia']) }}" 
                       id="concorrencia-container" 
                       class="w-full max-w-sm mx-auto thermometer-container block hover:shadow-lg hover:bg-gray-100 transition-all duration-200"
                       title="Ver produtos ativos sem concorrentes">
                        
                        <div class="thermometer-label">Status de Concorrência (Produtos Ativos)</div>
                        <div id="thermo-concorrentes-value" class="thermometer-value">
                            <span class="text-[#002D5A] font-bold">Com: 0 (0%)</span> | <span class="text-[#D00000] font-bold">Sem: 0 (0%)</span>
                        </div>
                        <div id="thermo-concorrentes-bar-bg" class="thermometer-bar-bg h-6">
                            <div id="thermo-concorrentes-fill-com" class="thermometer-bar-fill bg-[#002D5A]" style="width: 0%;"></div>
                            <div id="thermo-concorrentes-fill-sem" class="thermometer-bar-fill bg-[#D00000]" style="width: 0%;"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600 mt-2">
                            <span>Total de Produtos Ativos: <span id="total-produtos-concorrencia">0</span></span>
                        </div>
                    </a>
                </div>

                <div class="w-full md:w-1/2 flex flex-col items-center">
                    <a href="{{ route('dashboard.detalhes', ['filtro' => 'sem_concorrencia']) }}" 
                       id="pesquisa-container" 
                       class="w-full max-w-sm mx-auto thermometer-container block hover:shadow-lg hover:bg-gray-100 transition-all duration-200"
                       title="Ver produtos sem pesquisa hoje">
                        
                        <div class="thermometer-label">Status da Pesquisa (Hoje)</div>
                        <div id="thermo-pesquisa-value" class="thermometer-value">
                            <span class="text-blue-600 font-bold">Pesquisados: 0 (0%)</span> | <span class="text-gray-500 font-bold">Sem Pesquisa: 0 (0%)</span>
                        </div>
                        <div id="thermo-pesquisa-bar-bg" class="thermometer-bar-bg h-6">
                            <div id="thermo-pesquisa-fill-com" class="thermometer-bar-fill bg-blue-600" style="width: 0%;"></div>
                            <div id="thermo-pesquisa-fill-sem" class="thermometer-bar-fill bg-gray-400" style="width: 0%;"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600 mt-2">
                            <span>Total com Concorrentes: <span id="total-produtos-pesquisa">0</span></span>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="w-full">
                <h2 class="text-xl font-semibold text-gray-700 mb-6 text-center md:text-left">Participação por Concorrente Ativo</h2>
                @if (empty($competidores_ativos_data))
                    <p class="text-center text-gray-500 mt-4">Nenhum concorrente ativo encontrado com produtos ativos monitorados.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach ($competidores_ativos_data as $index => $competidor)
                            <div class="text-center p-2 border rounded-lg bg-gray-50 shadow-sm small-chart-container">
                                <h3 class="text-sm font-medium text-gray-600 truncate mb-1" title="{{ $competidor['nome'] }}">
                                    {{ $competidor['nome'] }}
                                </h3>
                                <canvas id="competidorChart-{{ $index }}"></canvas>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Dados injetados pelo Controller
            const competidoresData = {!! $chart_competidores_data_json !!};
            const totalComConcorrentes = {!! $total_com_concorrentes_json !!};
            const termometroPrecoData = {!! $termometro_preco_data_json !!};
            const termometroConcorrentesData = {!! $termometro_concorrentes_data_json !!};
            const statusPesquisaHojeData = {!! $status_pesquisa_hoje_json !!};

            // 1. Atualiza Termômetros de Preço
            if (termometroPrecoData && termometroPrecoData.total_monitorado > 0) {
                const totalMonitorado = termometroPrecoData.total_monitorado;

                const updateThermo = (id, value, total) => {
                    const percent = ((value / total) * 100).toFixed(1);
                    const elVal = document.getElementById(`thermo-${id}-value`);
                    const elFill = document.getElementById(`thermo-${id}-fill`);
                    if(elVal) elVal.textContent = `${value} / ${total}`;
                    if(elFill) elFill.style.width = `${percent}%`;
                };

                updateThermo('melhor', termometroPrecoData.melhor, totalMonitorado);
                updateThermo('media', termometroPrecoData.media, totalMonitorado);
                updateThermo('acima', termometroPrecoData.acima, totalMonitorado);
            }

            // 2. Atualiza Termômetro de Concorrência
            if (termometroConcorrentesData && termometroConcorrentesData.total > 0) {
                const total = termometroConcorrentesData.total;
                const com = termometroConcorrentesData.com;
                const sem = termometroConcorrentesData.sem;
                const pCom = ((com / total) * 100).toFixed(1);
                const pSem = ((sem / total) * 100).toFixed(1);

                document.getElementById('thermo-concorrentes-value').innerHTML = 
                    `<span class="text-[#002D5A] font-bold">Com: ${com} (${pCom}%)</span> | <span class="text-[#D00000] font-bold">Sem: ${sem} (${pSem}%)</span>`;
                
                document.getElementById('total-produtos-concorrencia').textContent = total;
                document.getElementById('thermo-concorrentes-fill-com').style.width = `${pCom}%`;
                document.getElementById('thermo-concorrentes-fill-sem').style.width = `${pSem}%`;
            }

            // 3. Atualiza Termômetro de Pesquisa
            if (statusPesquisaHojeData && statusPesquisaHojeData.total > 0) {
                const total = statusPesquisaHojeData.total;
                const pesq = statusPesquisaHojeData.pesquisados_hoje;
                const sem = statusPesquisaHojeData.sem_pesquisa_hoje;
                const pPesq = ((pesq / total) * 100).toFixed(1);
                const pSem = ((sem / total) * 100).toFixed(1);

                document.getElementById('thermo-pesquisa-value').innerHTML = 
                    `<span class="text-blue-600 font-bold">Pesquisados: ${pesq} (${pPesq}%)</span> | <span class="text-gray-500 font-bold">Sem: ${sem} (${pSem}%)</span>`;
                
                document.getElementById('total-produtos-pesquisa').textContent = total;
                document.getElementById('thermo-pesquisa-fill-com').style.width = `${pPesq}%`;
                document.getElementById('thermo-pesquisa-fill-sem').style.width = `${pSem}%`;
            }

            // 4. Gráficos de Pizza
            if (competidoresData && totalComConcorrentes > 0) {
                competidoresData.forEach((competidor, index) => {
                    const ctx = document.getElementById(`competidorChart-${index}`);
                    if (ctx) {
                        const count = competidor.count;
                        const outros = totalComConcorrentes - count;
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: [competidor.nome, 'Outros'],
                                datasets: [{
                                    data: [count, outros < 0 ? 0 : outros],
                                    backgroundColor: ['#002D5A', '#E5E7EB'],
                                    hoverOffset: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } }
                            }
                        });
                    }
                });
            }
        });
    </script>
@endsection