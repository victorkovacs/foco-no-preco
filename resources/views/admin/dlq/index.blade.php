@extends('layouts.app')

@section('title', 'Monitor de Erros (DLQ)')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Dead Letter Queue (DLQ)</h1>
            <p class="mt-2 text-sm text-gray-700">Lista de tarefas que falharam definitivamente após todas as tentativas.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <form action="{{ route('dlq.clear') }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar TODOS os erros?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto">
                    <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i> Limpar DLQ
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Data</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tarefa</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Erro</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Mensagem</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Worker</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($errors as $error)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 sm:pl-6">
                                        {{ \Carbon\Carbon::parse($error['failed_at'] ?? now())->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                                        {{ Str::afterLast($error['task_name'] ?? 'Unknown', '.') }}
                                        <div class="text-xs text-gray-400">{{ $error['task_id'] ?? '' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-red-600 font-semibold">
                                        {{ $error['error_type'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500 break-all max-w-xs">
                                        {{ Str::limit($error['error_message'] ?? '-', 100) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $error['worker_hostname'] ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i data-lucide="check-circle" class="w-12 h-12 text-green-500 mb-2"></i>
                                            <p class="text-lg font-medium">Nenhum erro na fila!</p>
                                            <p class="text-sm">Seu sistema está rodando perfeitamente.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection