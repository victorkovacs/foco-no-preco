@extends('layouts.app')

@section('title', 'Central de Monitoramento')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="sm:flex sm:items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Central de Monitoramento</h1>
            <p class="mt-1 text-sm text-gray-500">Visão unificada de erros de código (Sentry) e falhas de processamento (DLQ).</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <form action="{{ route('dlq.clear') }}" method="POST" onsubmit="return confirm('Tem certeza que deseja limpar a DLQ local?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    <i data-lucide="trash-2" class="w-4 h-4 mr-2 text-gray-500"></i> Limpar DLQ Local
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-sm" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <span class="flex h-2.5 w-2.5 rounded-full bg-red-500 mr-2 animate-pulse"></span>
                Erros Recentes (App & Frontend)
            </h2>
            <a href="https://sentry.io/organizations/{{ env('SENTRY_ORG_SLUG') }}/issues/" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center transition">
                Abrir Sentry <i data-lucide="external-link" class="w-4 h-4 ml-1"></i>
            </a>
        </div>

        <div class="flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Erro / Exceção</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Local (Culprit)</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Eventos</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Última Vez</th>
                                    <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($sentryIssues as $issue)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                            <div class="flex items-center">
                                                @if(str_contains(strtolower($issue['title']), 'js') || str_contains(strtolower($issue['platform'] ?? ''), 'javascript'))
                                                    <i data-lucide="monitor" class="w-4 h-4 text-yellow-500 mr-2" title="Frontend"></i>
                                                @else
                                                    <i data-lucide="server" class="w-4 h-4 text-blue-500 mr-2" title="Backend"></i>
                                                @endif
                                                {{ Str::limit($issue['title'], 60) }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500" title="{{ $issue['culprit'] ?? '' }}">
                                            {{ Str::limit($issue['culprit'] ?? 'N/A', 40) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                                {{ $issue['count'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($issue['lastSeen'])->locale('pt_BR')->diffForHumans() }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-medium">
                                            <a href="{{ $issue['permalink'] ?? '#' }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 flex items-center justify-end">
                                                Detalhes <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-500 bg-gray-50">
                                            <div class="flex flex-col items-center justify-center">
                                                <i data-lucide="check-circle" class="w-8 h-8 text-green-500 mb-2"></i>
                                                <p class="text-sm font-medium">Tudo limpo! Nenhum erro crítico aberto.</p>
                                                @if(empty(env('SENTRY_AUTH_TOKEN')))
                                                    <p class="text-xs text-red-400 mt-1">(Token API não configurado)</p>
                                                @endif
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

    <div class="border-t border-gray-200 my-8"></div>

    <div>
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i data-lucide="server-crash" class="text-gray-600 w-5 h-5"></i>
                Falhas de Workers (DLQ Local)
            </h2>
            <p class="text-sm text-gray-500">
                Tarefas do Celery que falharam definitivamente e precisam de análise manual.
                Exibindo últimos <strong class="text-indigo-600">{{ $safetyLimit }}</strong> de {{ $totalRedis }}.
            </p>
        </div>

        <div class="flex flex-col">
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
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($errors as $index => $error)
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
                                            {{ Str::limit($error['error_message'] ?? '-', 50) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <button onclick="openModal({{ $index }})" class="text-indigo-600 hover:text-indigo-900 flex items-center transition">
                                                <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Ver
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-12 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i data-lucide="check-circle" class="w-12 h-12 text-green-500 mb-2"></i>
                                                <p class="text-lg font-medium">Nenhum erro na fila local!</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                {{ $errors->links() }}
            </div>
        </div>
    </div>
</div>

<div id="detailModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Detalhes do Erro (Worker)</h3>
                        </div>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500"><i data-lucide="x" class="w-6 h-6"></i></button>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-4 sm:p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Argumentos (Args/Kwargs):</label>
                        <pre id="modal-args" class="mt-1 p-3 block w-full rounded-md bg-gray-800 text-green-400 text-xs overflow-auto max-h-40 font-mono"></pre>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Traceback:</label>
                        <pre id="modal-traceback" class="mt-1 p-3 block w-full rounded-md bg-gray-800 text-red-300 text-xs overflow-auto max-h-60 font-mono"></pre>
                    </div>
                    <div>
                         <label class="block text-sm font-medium text-gray-700">JSON Completo:</label>
                         <textarea id="modal-full" rows="3" readonly class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm bg-gray-100 text-gray-600"></textarea>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const dlqErrors = @json($errors->items());

    function openModal(index) {
        const error = dlqErrors[index];
        if (!error) return;

        document.getElementById('modal-args').textContent = JSON.stringify({ args: error.args, kwargs: error.kwargs }, null, 2);
        document.getElementById('modal-traceback').textContent = error.traceback || 'Sem traceback disponível.';
        document.getElementById('modal-full').value = JSON.stringify(error, null, 2);

        document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(event) {
        if(event.key === "Escape") closeModal();
    });
</script>
@endsection