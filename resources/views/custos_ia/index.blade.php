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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500">Tokens de Entrada (Input)</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($totalIn, 0, ',', '.') }}</h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <i data-lucide="arrow-down-left" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500">Tokens de Saída (Output)</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($totalOut, 0, ',', '.') }}</h3>
                </div>
                <div class="p-2 bg-green-50 rounded-lg text-green-600">
                    <i data-lucide="arrow-up-right" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500">Média Diária</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($mediaDiaria, 0, ',', '.') }}</h3>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                    <i data-lucide="activity" class="w-6 h-6"></i>
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
        const ctx = document.getElementById('tokensChart').getContext('2d');
        
        // Dados injetados pelo Blade
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
                        backgroundColor: '#3B82F6', // Azul
                        borderRadius: 4,
                    },
                    {
                        label: 'Output Tokens',
                        data: dataOut,
                        backgroundColor: '#10B981', // Verde
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
                        stacked: true, // Barras empilhadas (como no original)
                        grid: { color: '#f3f4f6' }
                    },
                    x: {
                        stacked: true,
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
    });
</script>
@endsection