@extends('layouts.app')

@section('title', 'Gerenciador de Seletores')

@section('content')
<div class="w-full max-w-7xl mx-auto bg-white shadow-xl rounded-2xl p-6 md:p-8">

    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Gerenciador de Seletores</h1>
    <p class="text-base text-gray-600 mb-6">
        Adicione ou edite os seletores e o status de cada concorrente.
    </p>

    <form method="GET" action="{{ route('concorrentes.index') }}" class="mb-4 flex gap-3 items-center flex-wrap">
        
        <div class="flex-shrink-0">
            <select name="filter_status" onchange="this.form.submit()" class="p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm">
                <option value="todos" {{ request('filter_status') == 'todos' ? 'selected' : '' }}>Todos Status</option>
                <option value="ativos" {{ request('filter_status') == 'ativos' ? 'selected' : '' }}>Ativos</option>
                <option value="inativos" {{ request('filter_status') == 'inativos' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>

        <input type="text" name="search" value="{{ request('search') }}" placeholder="Pesquisar nome..." class="flex-grow p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm">
        
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
            Filtrar
        </button>
        
        @if(request()->hasAny(['search', 'filter_status']))
            <a href="{{ route('concorrentes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Limpar</a>
        @endif
    </form>

    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach(['ID_Vendedor' => 'ID', 'NomeVendedor' => 'Nome', 'SeletorPreco' => 'Seletor Preço', 'Ativo' => 'Status'] as $col => $label)
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('concorrentes.index', array_merge(request()->query(), ['sort' => $col, 'dir' => request('dir') == 'ASC' ? 'DESC' : 'ASC'])) }}" class="group flex items-center gap-1 hover:text-gray-700">
                                {{ $label }}
                                <i data-lucide="arrow-up-down" class="w-3 h-3 text-gray-400 group-hover:text-gray-600"></i>
                            </a>
                        </th>
                    @endforeach
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($vendedores as $vendedor)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $vendedor->ID_Vendedor }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $vendedor->NomeVendedor }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs" title="{{ $vendedor->SeletorPreco }}">
                            {{ Str::limit($vendedor->SeletorPreco, 50) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($vendedor->Ativo)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inativo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="#" class="text-indigo-600 hover:text-indigo-900 flex items-center justify-end gap-1">
                                <i data-lucide="edit" class="w-4 h-4"></i> Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            Nenhum concorrente encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $vendedores->links() }}
    </div>

</div>
@endsection