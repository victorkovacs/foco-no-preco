@extends('layouts.app')

@section('title', $titulo)

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    {{-- CABEÇALHO E AÇÕES --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-dark text-sm flex items-center mb-2 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Voltar ao Dashboard
            </a>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center gap-3">
                <div class="p-2 rounded-lg 
                    @if($filtro == 'melhor') bg-green-100 text-green-700
                    @elseif($filtro == 'acima') bg-red-100 text-red-700
                    @elseif($filtro == 'media') bg-yellow-100 text-yellow-700
                    @else bg-gray-100 text-gray-700 @endif">
                    @if($filtro == 'melhor') <i data-lucide="trending-down" class="w-6 h-6"></i>
                    @elseif($filtro == 'acima') <i data-lucide="trending-up" class="w-6 h-6"></i>
                    @elseif($filtro == 'media') <i data-lucide="minus" class="w-6 h-6"></i>
                    @else <i data-lucide="list" class="w-6 h-6"></i>
                    @endif
                </div>
                {{ $titulo }}
            </h1>
            <p class="text-gray-500 mt-1 ml-1">
                Exibindo <strong class="text-gray-800">{{ count($produtos) }}</strong> produtos nesta categoria hoje.
            </p>
        </div>

        <div>
            <a href="{{ route('export.dashboard', ['filtro' => $filtro]) }}" 
               class="flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm transition-colors text-sm font-medium whitespace-nowrap">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 mr-2"></i>
                Baixar Excel ({{ ucfirst($filtro) }})
            </a>
        </div>
    </div>

    {{-- CONTEÚDO PRINCIPAL --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        {{-- VERSÃO DESKTOP (TABELA) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                        <th class="p-5 w-24">SKU</th>
                        <th class="p-5">Produto</th>
                        <th class="p-5 text-right whitespace-nowrap">Meu Preço</th>
                        <th class="p-5 text-right whitespace-nowrap">Melhor Concorrente</th>
                        <th class="p-5 text-right whitespace-nowrap">Média de Mercado</th>
                        <th class="p-5 text-right whitespace-nowrap">Diferença (vs Melhor)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produtos as $prod)
                        @php
                            $diff = 0; $diffPercent = 0; $hasDiff = false;
                            if ($prod->meu_preco && $prod->min_concorrente) {
                                $diff = $prod->meu_preco - $prod->min_concorrente;
                                $diffPercent = ($prod->min_concorrente > 0) ? ($diff / $prod->min_concorrente) * 100 : 0;
                                $hasDiff = true;
                            }
                        @endphp

                        <tr class="group hover:bg-blue-50/30 transition-colors duration-150">
                            
                            {{-- COLUNA SKU (LIMPA) --}}
                            <td class="p-5 align-middle">
                                <span class="font-mono text-xs font-bold text-gray-500">
                                    {{ $prod->SKU }}
                                </span>
                            </td>

                            {{-- COLUNA PRODUTO --}}
                            <td class="p-5 align-middle">
                                <div class="font-semibold text-gray-800 text-sm group-hover:text-blue-700 transition-colors line-clamp-2" title="{{ $prod->Nome }}">
                                    {{ $prod->Nome ?? 'Produto Sem Nome' }}
                                </div>
                            </td>
                            
                            {{-- MEU PREÇO --}}
                            <td class="p-5 text-right whitespace-nowrap align-middle">
                                <span class="font-bold text-gray-800 text-sm">
                                    R$ {{ number_format($prod->meu_preco, 2, ',', '.') }}
                                </span>
                            </td>
                            
                            {{-- MELHOR CONCORRENTE --}}
                            <td class="p-5 text-right whitespace-nowrap align-middle">
                                @if($prod->min_concorrente)
                                    <div class="text-sm font-medium text-gray-600">
                                        R$ {{ number_format($prod->min_concorrente, 2, ',', '.') }}
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs italic">Sem dados</span>
                                @endif
                            </td>

                            {{-- MÉDIA --}}
                            <td class="p-5 text-right whitespace-nowrap align-middle">
                                @if($prod->avg_concorrente)
                                    <div class="text-sm font-medium text-blue-600">
                                        R$ {{ number_format($prod->avg_concorrente, 2, ',', '.') }}
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs">-</span>
                                @endif
                            </td>

                            {{-- DIFERENÇA --}}
                            <td class="p-5 text-right whitespace-nowrap align-middle">
                                @if($hasDiff && abs($diff) > 0.01)
                                    <div class="flex flex-col items-end">
                                        <span class="text-sm font-bold {{ $diff > 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $diff > 0 ? '+' : '' }}R$ {{ number_format($diff, 2, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] font-medium {{ $diff > 0 ? 'text-red-400' : 'text-green-500' }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diffPercent, 1, ',', '.') }}%
                                        </span>
                                    </div>
                                @elseif($hasDiff)
                                    <span class="text-xs text-gray-400 font-medium">Empatado</span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <i data-lucide="package-search" class="w-12 h-12 mb-3 text-gray-200"></i>
                                    <p class="text-lg font-medium text-gray-500">Nenhum produto encontrado</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- VERSÃO MOBILE (CARDS) --}}
        <div class="md:hidden divide-y divide-gray-100">
            @forelse($produtos as $prod)
                @php
                    $diff = ($prod->meu_preco && $prod->min_concorrente) ? $prod->meu_preco - $prod->min_concorrente : 0;
                @endphp
                <div class="p-4 space-y-3 hover:bg-gray-50">
                    
                    {{-- Header do Card --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-mono text-xs font-bold text-gray-500">
                                {{ $prod->SKU }}
                            </span>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-800 line-clamp-2">{{ $prod->Nome ?? 'Produto Sem Nome' }}</h3>
                    </div>

                    {{-- Grid de Preços --}}
                    <div class="grid grid-cols-3 gap-2 bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                        <div>
                            <span class="block text-[10px] text-gray-500 uppercase tracking-wide">Eu</span>
                            <span class="block text-sm font-bold text-gray-900">
                                {{ $prod->meu_preco ? 'R$ '.number_format($prod->meu_preco, 2, ',', '.') : '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-gray-500 uppercase tracking-wide">Mínimo</span>
                            <span class="block text-sm font-medium text-gray-600">
                                {{ $prod->min_concorrente ? 'R$ '.number_format($prod->min_concorrente, 2, ',', '.') : '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-gray-500 uppercase tracking-wide">Média</span>
                            <span class="block text-sm font-medium text-blue-600">
                                {{ $prod->avg_concorrente ? 'R$ '.number_format($prod->avg_concorrente, 2, ',', '.') : '-' }}
                            </span>
                        </div>
                    </div>

                    {{-- Diferença --}}
                    @if($prod->min_concorrente && abs($diff) > 0.01)
                        <div class="flex justify-end items-center pt-1 border-t border-gray-100 mt-2">
                            <span class="text-xs font-medium mr-2 text-gray-500">Diferença (vs Mínimo):</span>
                            <span class="text-sm font-bold {{ $diff > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $diff > 0 ? '+' : '' }}R$ {{ number_format($diff, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <p>Nenhum produto encontrado.</p>
                </div>
            @endforelse
        </div>

    </div>
</div>

<script>
    lucide.createIcons();
</script>
@endsection