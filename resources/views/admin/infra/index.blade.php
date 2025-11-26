@extends('layouts.app')

@section('title', 'Infraestrutura')

@section('content')
<div class="flex flex-col w-full"> 
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i data-lucide="activity" class="inline w-8 h-8 mr-2 text-blue-600"></i>
            Monitoramento em Tempo Real
        </h1>
        <a href="http://localhost:3000/d/foconopreco-v1/foco-no-preco-monitoramento" target="_blank" class="text-sm text-blue-600 hover:underline">
            Abrir no Grafana <i data-lucide="external-link" class="inline w-4 h-4"></i>
        </a>
    </div>

    {{-- Container "Máscara" --}}
    <div class="w-full h-[530px] bg-[#161719] rounded-lg shadow-lg border border-gray-700 relative overflow-hidden">
        
        {{-- 
           TRUQUE DO ZOOM (Scale 0.8 = 80% do tamanho original)
           
           Matemática:
           Se diminuímos para 0.8 (80%), precisamos aumentar o tamanho real para compensar.
           100 / 0.8 = 125%
           
           Se quiser menor ainda (0.6):
           Width/Height = 100 / 0.6 = 166.6%
        --}}
        <iframe 
            src="http://localhost:3000/d/foconopreco-v1/foco-no-preco-monitoramento?orgId=1&refresh=5s&kiosk&theme=dark" 
            frameborder="0"
            class="absolute top-0 left-0 origin-top-left"
            style="
                width: 125%; 
                height: 125%; 
                transform: scale(0.80);
            "
        ></iframe>
    </div>
</div>
@endsection