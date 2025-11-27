@extends('layouts.app')

@section('title', 'Configuração de Rotinas')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i data-lucide="settings" class="inline w-8 h-8 mr-2 text-blue-600"></i>
            Agendamento de Rotinas
        </h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
        <form action="{{ route('admin.configuracoes.update') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($configs as $config)
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="{{ $config->chave }}">
                            {{ $config->descricao }}
                        </label>
                        
                        <div class="flex items-center">
                            @if($config->tipo == 'time')
                                <input type="time" 
                                       name="configs[{{ $config->chave }}]" 
                                       value="{{ $config->valor }}"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            @elseif($config->tipo == 'number')
                                <input type="number" 
                                       name="configs[{{ $config->chave }}]" 
                                       value="{{ $config->valor }}"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <span class="ml-2 text-gray-500 text-sm">horas</span>
                            @else
                                <input type="text" 
                                       name="configs[{{ $config->chave }}]" 
                                       value="{{ $config->valor }}"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Chave: {{ $config->chave }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    <i data-lucide="save" class="inline w-4 h-4 mr-1"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection