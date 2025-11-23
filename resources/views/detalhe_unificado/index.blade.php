@extends('layouts.app')

@section('title', $titulo)

@section('content')
<div class="w-full max-w-7xl mx-auto">
    
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-primary-dark text-sm flex items-center mb-2">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Voltar ao Dashboard
        </a>
        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
            @if($filtro == 'melhor') <i data-lucide="trending-down" class="mr-3 text-green-600"></i>
            @elseif($filtro == 'acima') <i data-lucide="trending-up" class="mr-3 text-red-600"></i>
            @elseif($filtro == 'media') <i data-lucide="minus" class="mr-3 text-yellow-500"></i>
            @else <i data-lucide="list" class="mr-3 text-primary-dark"></i>
            @endif
            {{ $titulo }}
        </h1>
        <p class="text-gray-600 mt-1">{{ count($produtos) }} produtos encontrados nesta categoria hoje.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                        <th class="p-4">Produto</th>
                        <th class="p-4 text-right">Meu Preço</th>
                        <th class="p-4 text-right">Mín. Concorrente</th>
                        <th class="p-4 text-right">Média Mercado</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produtos as $prod)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4">
                                <div class="font-medium text-gray-800">{{ $prod->Nome }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $prod->SKU }}</div>
                            </td>
                            <td class="p-4 text-right font-bold text-primary-dark">
                                R$ {{ number_format($prod->meu_preco, 2, ',', '.') }}
                            </td>
                            <td class="p-4 text-right text-gray-600">
                                @if($prod->min_concorrente)
                                    R$ {{ number_format($prod->min_concorrente, 2, ',', '.') }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-4 text-right text-gray-600">
                                @if($prod->avg_concorrente)
                                    R$ {{ number_format($prod->avg_concorrente, 2, ',', '.') }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($prod->status_preco == 'melhor')
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full font-medium">Ganho</span>
                                @elseif($prod->status_preco == 'acima')
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-medium">Perda</span>
                                @elseif($prod->status_preco == 'media')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-medium">Médio</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">N/A</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($prod->LinkMeuSite)
                                    <a href="{{ $prod->LinkMeuSite }}" target="_blank" class="text-blue-600 hover:underline text-xs flex justify-center items-center">
                                        Ver <i data-lucide="external-link" class="w-3 h-3 ml-1"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                Nenhum produto encontrado com este filtro hoje.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection