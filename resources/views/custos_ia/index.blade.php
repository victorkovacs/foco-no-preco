@extends('layouts.app')

@section('title', 'Custos de IA')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<div class="w-full max-w-7xl mx-auto">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <i data-lucide="coins" class="mr-3 text-primary-dark"></i>
                Consumo de Inteligência Artificial
            </h1>
            <p class="text-gray-500 text-sm mt-1">Monitore o uso de tokens e custos da API.</p>
        </div>

        <div class="flex bg-white rounded-lg shadow-sm p-1 border border-gray-200">
            <a href="{{ route('custos_ia.index', ['filtro' => 7]) }}" 
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $dias == 7 ? 'bg-primary-dark text-white' : 'text-gray-600 hover:bg-gray-50' }}">
               7 Dias
            </a>
            <a href="{{ route('custos_ia.index', ['filtro' => 30]) }}" 
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $dias == 30 ? 'bg-primary-dark text-white' : 'text-gray-600 hover:bg-gray-50' }}">
               30 Dias
            </a>
            <a href="{{ route('custos_ia.index', ['filtro' => 60]) }}" 
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $dias == 60 ? 'bg-primary-dark text-white' : 'text-gray-600 hover:bg-gray-50' }}">
               60 Dias
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Input Tokens</p>
                    <h3 class="text-xl font-bold text-gray-800 mt-1">{{ number_format($totalIn, 0, ',', '.') }}</h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <i data-lucide="arrow-down-left" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Output Tokens</p>
                    <h3 class="text-xl font-bold text-gray-800 mt-1">{{ number_format($totalOut, 0, ',', '.') }}</h3>
                </div>
                <div class="p-2 bg-green-50 rounded-lg text-green-600">
                    <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Média Diária</p>
                    <h3 class="text-xl font-bold text-gray-800 mt-1">{{ number_format($mediaDiaria, 0, ',', '.') }}</h3>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1 bg-yellow-400"></div>
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-yellow-600 uppercase tracking-wider">Custo Estimado (R$)</p>
                    <h3 class="text-2xl font-extrabold text-gray-800 mt-1">
                        R$ {{ number_format($custoTotalBRL, 2, ',', '.') }}
                    </h3>
                    <p class="text-[10px] text-gray-400 mt-1">Cotação ref. US$ 1 = R$ 6,10</p>
                </div>
                <div class="p-2 bg-yellow-50 rounded-lg text-yellow-600">
                    <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Evolução do Consumo</h3>
        <div class="h-80 w-full">
            <canvas id="tokensChart"></canvas>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        const ctx = document.getElementById('tokensChart').getContext('2d');
        
        const labels = @json($graficoLabels);
        const dataIn = @json($graficoDataIn);
        const dataOut = @json($graficoDataOut);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Input Tokens',
                        data: dataIn,
                        backgroundColor: '#3B82F6',
                        borderRadius: 4,
                    },
                    {
                        label: 'Output Tokens',
                        data: dataOut,
                        backgroundColor: '#10B981',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        grid: { color: '#f3f4f6' }
                    },
                    x: {
                        stacked: true,
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });
    });
</script>
@endsection