@extends('layouts.app')

@section('title', 'Atualização em Massa')

@section('content')
<div class="w-full max-w-4xl mx-auto">
    
    <div class="mb-6">
        <a href="{{ route('produtos.gerenciar') }}" class="text-gray-500 hover:text-primary-dark text-sm flex items-center mb-2">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Voltar para Edição
        </a>
        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
            <i data-lucide="layers" class="mr-3 text-primary-dark"></i>
            Atualizar Produtos em Massa
        </h1>
        <p class="text-gray-600 mt-1">Cole uma lista de SKUs para alterar o status rapidamente.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
        
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <form action="{{ route('produtos.mass_update_process') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label for="skus" class="block text-sm font-medium text-gray-700 mb-2">
                    Lista de SKUs (um por linha ou separado por vírgula)
                </label>
                <textarea name="skus" id="skus" rows="10" required
                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark focus:border-primary-dark font-mono text-sm"
                          placeholder="SKU001&#10;SKU002&#10;SKU003"></textarea>
            </div>

            <div class="mb-6">
                <label for="novo_status" class="block text-sm font-medium text-gray-700 mb-2">
                    Novo Status para estes Produtos
                </label>
                <select name="novo_status" id="novo_status" class="w-full p-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-dark">
                    <option value="1">Ativo (Monitorar)</option>
                    <option value="0">Inativo (Parar Monitoramento)</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('produtos.gerenciar') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium shadow-sm transition-colors flex items-center">
                    <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                    Aplicar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection