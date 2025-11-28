@extends('layouts.app')

@section('title', 'Painel de Geração de Conteúdo')

@section('content')
<div class="container mx-auto p-4 md:p-8 max-w-7xl">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Painel de Geração de Conteúdo</h1>

    {{-- CARDS DE STATUS --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <a href="{{ route('produtos_dashboard.index') }}" class="bg-white p-4 rounded-lg shadow-md flex flex-col items-center justify-center cursor-pointer hover:shadow-lg transition-shadow border border-transparent hover:border-gray-200 group">
            <span class="text-sm font-medium text-gray-500 mb-2">Total de Produtos</span>
            <span class="text-3xl font-bold text-gray-700 group-hover:text-blue-600">{{ $stats['total_fila'] }}</span>
        </a>
        
        <a href="{{ route('produtos_dashboard.index', ['status' => 'pendente']) }}" class="bg-white p-4 rounded-lg shadow-md flex flex-col items-center justify-center cursor-pointer hover:shadow-lg transition-shadow border border-transparent hover:border-yellow-200 group">
            <span class="text-sm font-medium text-gray-500 mb-2">Pendentes</span>
            <span class="text-3xl font-bold text-yellow-500 group-hover:text-yellow-600">{{ $stats['pendente'] }}</span>
        </a>

        <a href="{{ route('produtos_dashboard.index', ['status' => 'processando']) }}" class="bg-white p-4 rounded-lg shadow-md flex flex-col items-center justify-center cursor-pointer hover:shadow-lg transition-shadow border border-transparent hover:border-purple-200 group">
            <span class="text-sm font-medium text-gray-500 mb-2">Processando</span>
            <span class="text-3xl font-bold text-purple-500 group-hover:text-purple-600">{{ $stats['processando'] }}</span>
        </a>

        <a href="{{ route('produtos_dashboard.index', ['status' => 'concluido']) }}" class="bg-white p-4 rounded-lg shadow-md flex flex-col items-center justify-center cursor-pointer hover:shadow-lg transition-shadow border border-transparent hover:border-green-200 group">
            <span class="text-sm font-medium text-gray-500 mb-2">Concluídos</span>
            <span class="text-3xl font-bold text-green-500 group-hover:text-green-600">{{ $stats['concluido'] }}</span>
        </a>

        <a href="{{ route('produtos_dashboard.index', ['status' => 'erro']) }}" class="bg-white p-4 rounded-lg shadow-md flex flex-col items-center justify-center cursor-pointer hover:shadow-lg transition-shadow border border-transparent hover:border-red-200 group">
            <span class="text-sm font-medium text-gray-500 mb-2">Falhas</span>
            <span class="text-3xl font-bold text-red-500 group-hover:text-red-600">{{ $stats['falhou'] }}</span>
        </a>
    </div>

    {{-- ÁREA DE INSERÇÃO --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        {{-- Form Manual --}}
        <div class="bg-white p-6 rounded-lg shadow-md flex flex-col border border-gray-100">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Adicionar Novo Produto (Manual)
            </h2>
            <form id="form-novo-produto" class="flex flex-col gap-4 h-full">
                @csrf
                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700">SKU (Código)</label>
                    <input type="text" id="sku" name="sku" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: SKU-PROMO-006">
                </div>
                <div>
                    <label for="palavra_chave" class="block text-sm font-medium text-gray-700">Palavra-Chave de Entrada</label>
                    <input type="text" id="palavra_chave" name="palavra_chave" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: Furadeira de Impacto 500W">
                </div>
                
                <div>
                    <label for="id_template_manual" class="block text-sm font-medium text-gray-700">Template de Geração (IA)</label>
                    <select id="id_template_manual" name="id_template_manual" required class="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm">
                        <option value="">-- Selecione um Template --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->nome_template }}</option>
                        @endforeach
                    </select>
                </div>
                
                <button type="submit" id="btn-submit-manual" class="mt-auto w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Adicionar à Fila (Manual)
                </button>
            </form>
            <div id="form-mensagem" class="mt-3 text-sm hidden"></div>
        </div>

        {{-- Form Massivo --}}
        <div class="bg-white p-6 rounded-lg shadow-md flex flex-col border border-gray-100">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                Importar em Massa (Planilha CSV)
            </h2>
            <form id="form-upload-massa" class="flex flex-col gap-4 h-full">
                @csrf
                <div>
                    <label for="arquivo_csv" class="block text-sm font-medium text-gray-700">Arquivo CSV</label>
                    <input type="file" id="arquivo_csv" name="arquivo_csv" required accept=".csv,.txt" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                
                {{-- INSTRUÇÕES E LINK DE DOWNLOAD DINÂMICO --}}
                <div class="bg-blue-50 p-4 rounded-md text-xs text-blue-800">
                    <p class="font-bold mb-2">Instruções:</p>
                    <ul class="list-disc list-inside mb-3 space-y-1">
                        <li>Arquivo <b>.csv</b> (separado por ponto e vírgula `;`)</li>
                        <li>Colunas: <code class="bg-white px-1 py-0.5 rounded border border-blue-200 font-mono">SKU, PALAVRA_CHAVE, ID_TEMPLATE</code></li>
                    </ul>
                    
                    <a href="{{ route('produtos_dashboard.template') }}" class="inline-flex items-center text-blue-700 hover:text-blue-900 font-semibold hover:underline">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Baixar Planilha Modelo
                    </a>
                </div>

                <button type="submit" id="btn-submit-upload" class="mt-auto w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                    Importar e Adicionar à Fila
                </button>
            </form>
            <div id="upload-mensagem" class="mt-3 text-sm hidden"></div>
        </div>
    </div>

    {{-- FILA DE PROCESSAMENTO --}}
    <div class="px-6 py-5 border-b border-gray-200 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    {{-- Título e Contador --}}
    <div class="flex items-center gap-2">
        <h2 class="text-lg font-bold text-gray-800">Fila de Processamento</h2>
        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
            {{ $itensFila->total() }} itens
        </span>
    </div>

    {{-- Área de Filtros --}}
    <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
        
        <form method="GET" action="{{ route('produtos_dashboard.index') }}" class="contents">
            
            {{-- 1. CAMPO DE BUSCA (ESQUERDA) --}}
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm shadow-sm transition-shadow" 
                       placeholder="Buscar SKU...">
            </div>

            {{-- 2. DROPDOWN ESTILIZADO (DIREITA DA BUSCA) --}}
            <div class="relative w-full sm:w-48">
                {{-- O select real (com appearance-none para esconder a seta padrão) --}}
                <select name="status" 
                        onchange="this.form.submit()"
                        class="appearance-none block w-full pl-3 pr-10 py-2 text-base border border-gray-300 bg-white rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm shadow-sm cursor-pointer hover:border-gray-400 transition-colors">
                    <option value="">Todos os Status</option>
                    <option value="pendente" {{ request('status') == 'pendente' ? 'selected' : '' }}>Pendente</option>
                    <option value="processando" {{ request('status') == 'processando' ? 'selected' : '' }}>Processando</option>
                    <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                    <option value="erro" {{ request('status') == 'erro' ? 'selected' : '' }}>Falha/Erro</option>
                </select>
                
                {{-- Ícone da Seta Customizado (Posicionado Absolutamente) --}}
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </form>

        {{-- 3. BOTÃO LIMPAR (EXTREMA DIREITA) --}}
        @if(request('search') || request('status'))
            <a href="{{ route('produtos_dashboard.index') }}" 
               class="shrink-0 inline-flex items-center justify-center w-full sm:w-auto px-3 py-2 border border-red-200 shadow-sm text-sm font-medium rounded-lg text-red-700 bg-red-50 hover:bg-red-100 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all"
               title="Limpar todos os filtros">
                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Limpar
            </a>
        @endif
    </div>
</div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100 text-xs uppercase text-gray-500 font-semibold">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left">ID</th>
                        <th scope="col" class="px-6 py-3 text-left">SKU / Produto</th>
                        <th scope="col" class="px-6 py-3 text-left">Palavra-Chave</th>
                        <th scope="col" class="px-6 py-3 text-left">Template</th>
                        <th scope="col" class="px-6 py-3 text-center">Status</th>
                        <th scope="col" class="px-6 py-3 text-right">Data</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($itensFila as $tarefa)
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">#{{ $tarefa->id_fila }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $tarefa->nome_produto }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $tarefa->sku }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $tarefa->palavra_chave_entrada }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $tarefa->nome_template ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($tarefa->status == 'concluido')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Concluído</span>
                            @elseif($tarefa->status == 'processando')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 animate-pulse">Processando</span>
                            @elseif($tarefa->status == 'pendente')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendente</span>
                            @elseif($tarefa->status == 'erro')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" title="{{ $tarefa->mensagem_erro }}">Falha</span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ $tarefa->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($tarefa->data_entrada)->format('d/m H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            Nenhum item na fila. Utilize os formulários acima para começar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $itensFila->links() }}
        </div>
    </div>
</div>

<script>
    // --- LÓGICA DE ENVIO MANUAL ---
    document.getElementById('form-novo-produto').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit-manual');
        const msg = document.getElementById('form-mensagem');
        
        btn.disabled = true;
        btn.innerText = 'Enviando...';
        msg.classList.add('hidden');

        try {
            const response = await fetch("{{ route('produtos_dashboard.store') }}", {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();

            msg.classList.remove('hidden');
            if (data.success) {
                msg.className = 'mt-3 text-sm text-green-600 font-bold';
                msg.innerText = data.message;
                this.reset();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                msg.className = 'mt-3 text-sm text-red-600 font-bold';
                msg.innerText = data.message || 'Erro ao salvar.';
            }
        } catch (error) {
            console.error(error);
            msg.classList.remove('hidden');
            msg.className = 'mt-3 text-sm text-red-600 font-bold';
            msg.innerText = 'Erro de conexão.';
        } finally {
            btn.disabled = false;
            btn.innerText = 'Adicionar à Fila (Manual)';
        }
    });

    // --- LÓGICA DE UPLOAD MASSIVO ---
    document.getElementById('form-upload-massa').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit-upload');
        const msg = document.getElementById('upload-mensagem');
        
        btn.disabled = true;
        btn.innerText = 'Processando CSV...';
        msg.classList.add('hidden');

        try {
            const response = await fetch("{{ route('produtos_dashboard.import') }}", {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();

            msg.classList.remove('hidden');
            if (data.success) {
                msg.className = 'mt-3 text-sm text-green-600 font-bold';
                msg.innerText = data.message;
                this.reset();
                setTimeout(() => window.location.reload(), 2000);
            } else {
                msg.className = 'mt-3 text-sm text-red-600 font-bold';
                msg.innerText = data.message || 'Erro ao importar.';
            }
        } catch (error) {
            console.error(error);
            msg.classList.remove('hidden');
            msg.className = 'mt-3 text-sm text-red-600 font-bold';
            msg.innerText = 'Erro de conexão ou timeout.';
        } finally {
            btn.disabled = false;
            btn.innerText = 'Importar e Adicionar à Fila';
        }
    });
</script>
@endsection