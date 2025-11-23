@extends('layouts.app')

@section('title', 'Painel de Geração de Conteúdo')

@section('content')
<div class="w-full max-w-7xl mx-auto">
    
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Painel de Geração de Conteúdo</h1>
        <div class="flex gap-2">
            <button onclick="document.getElementById('modal-novo-produto').classList.remove('hidden')" class="bg-primary-dark hover:bg-primary-darker text-white px-4 py-2 rounded-lg flex items-center shadow-md transition-colors">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Novo Produto
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('produtos_dashboard.index') }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer group">
            <span class="text-sm font-medium text-gray-500">Total de Produtos</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg group-hover:bg-blue-100 transition-colors">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('produtos_dashboard.index', ['status' => 'pendente']) }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer group">
            <span class="text-sm font-medium text-gray-500">Em Espera</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-bold text-yellow-600">{{ $stats['pendente'] }}</span>
                <div class="p-2 bg-yellow-50 text-yellow-600 rounded-lg group-hover:bg-yellow-100 transition-colors">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
        </a>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 opacity-75">
            <span class="text-sm font-medium text-gray-500">Processando</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-bold text-blue-600">0</span>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <i data-lucide="loader-2" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <a href="{{ route('produtos_dashboard.index', ['status' => 'concluido']) }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow cursor-pointer group">
            <span class="text-sm font-medium text-gray-500">Concluídos</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-bold text-green-600">{{ $stats['concluido'] }}</span>
                <div class="p-2 bg-green-50 text-green-600 rounded-lg group-hover:bg-green-100 transition-colors">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col md:flex-row gap-4 justify-between items-center">
        <div class="flex items-center gap-2 w-full md:w-auto">
            <button class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors" title="Processar Lote (Simulado)">
                <i data-lucide="play" class="w-4 h-4"></i>
            </button>
            <button class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors" title="Atualizar">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="GET" action="{{ route('produtos_dashboard.index') }}" class="flex gap-2 w-full md:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar produto..." class="p-2 border border-gray-300 rounded-lg text-sm w-full md:w-64 focus:ring-2 focus:ring-primary-dark focus:outline-none">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium text-sm">Buscar</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                <tr>
                    <th class="p-4 w-10"><input type="checkbox" class="rounded border-gray-300"></th>
                    <th class="p-4">Produto</th>
                    <th class="p-4 text-center">Status IA</th>
                    <th class="p-4 hidden md:table-cell">Template</th>
                    <th class="p-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($produtos as $produto)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4"><input type="checkbox" class="rounded border-gray-300"></td>
                        <td class="p-4">
                            <div class="font-medium text-gray-800">{{ $produto->Nome }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $produto->SKU }}</div>
                        </td>
                        <td class="p-4 text-center">
                            @if($produto->ia_processado == 1)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Concluído
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Em Espera
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-sm text-gray-600 hidden md:table-cell">
                            {{ $produto->id_template_ia ? 'Template #' . $produto->id_template_ia : '-' }}
                        </td>
                        <td class="p-4 text-center">
                            <button class="text-gray-400 hover:text-blue-600 transition-colors">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-gray-500">Nenhum produto encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-200">
            {{ $produtos->links() }}
        </div>
    </div>
</div>

<div id="modal-novo-produto" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Novo Produto</h3>
        <form action="{{ route('produtos_dashboard.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto</label>
                    <input type="text" name="nome" required class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" name="sku" required class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template IA (Opcional)</label>
                    <select name="template_id" class="w-full p-2 border border-gray-300 rounded-lg bg-white">
                        <option value="">Selecione...</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->nome_template }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-novo-produto').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary-dark text-white rounded-lg hover:bg-opacity-90 transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>

@endsection