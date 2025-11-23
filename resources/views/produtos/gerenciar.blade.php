@extends('layouts.app')

@section('title', 'Edição de Produtos')

@section('content')
<div class="w-full max-w-7xl mx-auto">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <i data-lucide="edit-3" class="mr-3 text-primary-dark"></i>
                Edição dos Produtos
            </h1>
            <p class="text-gray-500 text-sm mt-1">Gerencie o catálogo, categorias e status.</p>
        </div>
        
        <a href="{{ route('produtos.mass_update') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg flex items-center transition-colors shadow-sm font-medium text-sm">
            <i data-lucide="layers" class="w-4 h-4 mr-2"></i>
            Atualização em Massa
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form method="GET" action="{{ route('produtos.gerenciar') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="pl-10 w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                           placeholder="SKU ou Nome...">
                </div>
            </div>

            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                <select name="filter_marca" onchange="this.form.submit()"
                        class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                    <option value="">Todas as Marcas</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca }}" {{ request('filter_marca') == $marca ? 'selected' : '' }}>
                            {{ $marca }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <a href="{{ route('produtos.gerenciar') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2.5 rounded-lg font-medium transition-colors text-sm flex-1 text-center">
                    Limpar
                </a>
                <button type="submit" class="bg-primary-dark text-white px-4 py-2.5 rounded-lg font-medium hover:bg-opacity-90 transition-colors text-sm flex-1">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                        <th class="p-4 w-32">SKU</th>
                        <th class="p-4">Produto</th>
                        <th class="p-4 hidden md:table-cell">Categoria</th>
                        <th class="p-4 hidden md:table-cell">Marca</th>
                        <th class="p-4 text-center">Ativo</th>
                        <th class="p-4 w-24 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produtos as $produto)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="p-4 font-mono text-sm font-bold text-gray-700">{{ $produto->SKU }}</td>
                            <td class="p-4">
                                <div class="font-medium text-gray-800">{{ $produto->Nome }}</div>
                                @if($produto->LinkMeuSite)
                                    <a href="{{ $produto->LinkMeuSite }}" target="_blank" class="text-xs text-blue-500 hover:underline flex items-center mt-1">
                                        Link Site <i data-lucide="external-link" class="w-3 h-3 ml-1"></i>
                                    </a>
                                @endif
                            </td>
                            <td class="p-4 text-sm text-gray-600 hidden md:table-cell">
                                {{ $produto->Categoria ?: '-' }}
                            </td>
                            <td class="p-4 text-sm text-gray-600 hidden md:table-cell">
                                <span class="px-2 py-1 rounded bg-gray-100 text-xs border border-gray-200">
                                    {{ $produto->marca ?: 'N/D' }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                @if($produto->ativo)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                        Sim
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                        Não
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="#" class="p-1.5 hover:bg-blue-50 text-blue-600 rounded-lg transition-colors" title="Editar">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    
                                    <form action="{{ route('produtos.destroy', $produto->ID) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 hover:bg-red-50 text-red-600 rounded-lg transition-colors" title="Excluir">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                Nenhum produto encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-gray-200">
            {{ $produtos->links() }}
        </div>
    </div>
</div>
@endsection