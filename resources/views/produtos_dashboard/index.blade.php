@extends('layouts.app')

@section('title', 'Monitor da Fila de IA')

@section('content')
<div class="w-full max-w-7xl mx-auto">
    
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                <i data-lucide="layers" class="mr-3 text-primary-dark"></i>
                Monitor da Fila de Geração
            </h1>
            <p class="text-gray-500 mt-1">Acompanhe em tempo real o trabalho dos robôs na tabela <b>FilaGeracaoConteudo</b>.</p>
        </div>
        
        {{-- Botão Principal de Ação --}}
        <button onclick="openTaskModal()" class="bg-primary-dark text-white px-6 py-3 rounded-lg flex items-center shadow-lg hover:bg-opacity-90 transition-all">
            <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i> 
            Nova Geração em Massa
        </button>
    </div>

    {{-- Cards de Estatísticas da Fila --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- Card 1: Total na Fila --}}
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <span class="text-xs font-bold text-gray-400 uppercase">Total na Fila (Histórico)</span>
            {{-- CORREÇÃO: Usamos 'total_fila' que vem do controller novo --}}
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_fila'] }}</div>
        </div>

        {{-- Card 2: Pendentes --}}
        <a href="{{ route('produtos_dashboard.index', ['status' => 'pendente']) }}" class="bg-yellow-50 p-4 rounded-xl border border-yellow-200 cursor-pointer hover:bg-yellow-100 transition-colors">
            <span class="text-xs font-bold text-yellow-600 uppercase">Aguardando Robô</span>
            <div class="text-2xl font-bold text-yellow-700 mt-1">{{ $stats['pendente'] }}</div>
        </a>

        {{-- Card 3: Processando --}}
        <a href="{{ route('produtos_dashboard.index', ['status' => 'processando']) }}" class="bg-blue-50 p-4 rounded-xl border border-blue-200 cursor-pointer hover:bg-blue-100 transition-colors">
            <span class="text-xs font-bold text-blue-600 uppercase">Processando Agora</span>
            <div class="text-2xl font-bold text-blue-700 mt-1 flex items-center">
                {{ $stats['processando'] }}
                @if($stats['processando'] > 0)
                    <i data-lucide="loader-2" class="ml-2 w-5 h-5 animate-spin"></i>
                @endif
            </div>
        </a>

        {{-- Card 4: Concluídos --}}
        <div class="bg-green-50 p-4 rounded-xl border border-green-200">
            <span class="text-xs font-bold text-green-600 uppercase">Concluídos</span>
            <div class="text-2xl font-bold text-green-700 mt-1">{{ $stats['concluido'] }}</div>
        </div>
    </div>

    {{-- Filtros e Busca --}}
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('produtos_dashboard.index') }}" class="text-sm text-gray-500 hover:text-primary-dark underline">
            Limpar Filtros
        </a>
        <form method="GET" action="{{ route('produtos_dashboard.index') }}" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar Nome ou SKU..." class="p-2 border border-gray-300 rounded-lg text-sm w-64">
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm">Buscar</button>
        </form>
    </div>

    {{-- Tabela da Fila --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold">
                <tr>
                    <th class="p-4 w-16">ID Fila</th>
                    <th class="p-4">Produto / SKU</th>
                    <th class="p-4">Template IA</th>
                    <th class="p-4 text-center">Status</th>
                    <th class="p-4 text-right">Atualização</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($itensFila as $tarefa)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4 text-gray-400 text-xs">#{{ $tarefa->id_fila }}</td>
                        <td class="p-4">
                            {{-- Aqui usamos os dados diretos da fila (sku e nome_produto) --}}
                            <div class="font-medium text-gray-800">{{ $tarefa->nome_produto }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $tarefa->sku }}</div>
                        </td>
                        <td class="p-4 text-sm text-gray-600">
                            {{ $tarefa->nome_template ?? 'Padrão' }}
                        </td>
                        <td class="p-4 text-center">
                            @if($tarefa->status == 'concluido')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Concluído
                                </span>
                            @elseif($tarefa->status == 'processando')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 animate-pulse">
                                    <i data-lucide="loader-2" class="w-3 h-3 mr-1 animate-spin"></i> Processando
                                </span>
                            @elseif($tarefa->status == 'pendente')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Na Fila
                                </span>
                            @elseif($tarefa->status == 'erro')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="{{ $tarefa->mensagem_erro }}">
                                    Erro
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-right text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($tarefa->data_atualizacao)->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center text-gray-400">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                            <p>A fila está vazia.</p>
                            <button onclick="openTaskModal()" class="text-primary-dark font-medium hover:underline mt-2">Adicionar tarefas agora</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $itensFila->links() }}
        </div>
    </div>
</div>

{{-- MODAL: Adicionar Tarefas (SKUs) --}}
<div id="modal-task" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Adicionar à Fila</h3>
        <p class="text-sm text-gray-500 mb-4">Cole os SKUs dos produtos que você quer processar (um por linha).</p>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Template de IA</label>
                <select id="task_template_id" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none">
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->nome_template }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lista de SKUs</label>
                <textarea id="task_skus" rows="6" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark font-mono text-sm" placeholder="SKU-001&#10;SKU-002&#10;SKU-003"></textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t pt-4">
            <button onclick="document.getElementById('modal-task').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="submitTasks()" id="btn-submit-task" class="px-4 py-2 bg-primary-dark text-white rounded-lg hover:bg-opacity-90 flex items-center">
                <span>Adicionar à Fila</span>
                <i id="loading-task" data-lucide="loader-2" class="w-4 h-4 ml-2 animate-spin hidden"></i>
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });

    function openTaskModal() {
        document.getElementById('modal-task').classList.remove('hidden');
    }

    function submitTasks() {
        const skus = document.getElementById('task_skus').value;
        const templateId = document.getElementById('task_template_id').value;
        const btn = document.getElementById('btn-submit-task');
        const loading = document.getElementById('loading-task');

        if(!skus.trim()) {
            alert('Por favor, insira pelo menos um SKU.');
            return;
        }

        // Bloqueia botão
        btn.disabled = true;
        btn.classList.add('opacity-70');
        loading.classList.remove('hidden');

        fetch("{{ route('produtos_dashboard.processar') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ skus: skus, template_id: templateId })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão ao tentar enviar tarefas.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.classList.remove('opacity-70');
            loading.classList.add('hidden');
        });
    }
</script>
@endsection