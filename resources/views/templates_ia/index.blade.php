@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 md:p-8 max-w-7xl">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Gestão de Templates (IA)</h1>
        <button onclick="abrirModalNovo()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            + Adicionar Novo Template
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Atualização</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($templates as $template)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#{{ $template->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $template->nome }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $template->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="abrirModalEditar({{ $template->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                        <form action="{{ route('templates_ia.destroy', $template->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum template cadastrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $templates->links() }}
        </div>
    </div>
</div>

<div id="modal-template" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="fecharModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-titulo">Novo Template</h3>
                
                <form id="form-template">
                    <input type="hidden" id="template_id">

                    <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                        <label class="block text-sm font-bold text-blue-800 mb-2">
                            ✨ Gerador Automático de Prompt (IA)
                        </label>
                        <p class="text-xs text-blue-600 mb-2">
                            Cole abaixo um exemplo PERFEITO do resultado que você quer. A IA fará a engenharia reversa para criar o prompt e o schema para você.
                        </p>
                        <textarea id="exemplo_saida" rows="3" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="Cole aqui: JSON de exemplo, Texto de venda, Descrição de produto..."></textarea>
                        <button type="button" id="btn-gerar-automatico" onclick="gerarAutomatico()" class="mt-2 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:w-auto sm:text-sm">
                            🪄 Gerar Prompt e Schema com IA
                        </button>
                        <span id="loading-ia" class="ml-2 text-sm text-gray-500 hidden">Processando... aguarde...</span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="nome" class="block text-sm font-medium text-gray-700">Nome do Template</label>
                            <input type="text" id="nome_template" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="prompt_sistema" class="block text-sm font-medium text-gray-700">Prompt de Sistema (Instruções)</label>
                                <textarea id="prompt_sistema" rows="10" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50 font-mono text-xs"></textarea>
                            </div>
                            <div>
                                <label for="json_schema_saida" class="block text-sm font-medium text-gray-700">JSON Schema de Saída (Estrutura)</label>
                                <textarea id="json_schema_saida" rows="10" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50 font-mono text-xs"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="salvarTemplate()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Salvar Template
                </button>
                <button type="button" onclick="fecharModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Rotas do Laravel para o JS
    const ROTA_SALVAR = "{{ route('templates_ia.store') }}"; 
    const ROTA_GERAR_AUTO = "{{ route('templates.gerar_automatico') }}";
    
    // Token CSRF para requisições AJAX
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function abrirModalNovo() {
        document.getElementById('modal-titulo').innerText = 'Novo Template';
        document.getElementById('template_id').value = '';
        document.getElementById('form-template').reset();
        document.getElementById('modal-template').classList.remove('hidden');
    }

    function fecharModal() {
        document.getElementById('modal-template').classList.add('hidden');
    }

    // Função para carregar dados (Edição) - Ajuste conforme sua rota de 'show' ou passe os dados via data-attributes no botão
    async function abrirModalEditar(id) {
        // Exemplo simples: Fetch dos dados ou preenchimento se tiver o objeto em JS
        // Aqui assumo que você implementará um fetch ou passará os dados via JSON no blade
        // Para simplificar, vou deixar o esqueleto:
        alert('Implementar lógica de buscar dados do ID: ' + id);
        
        // document.getElementById('modal-template').classList.remove('hidden');
    }

    async function gerarAutomatico() {
        const exemplo = document.getElementById('exemplo_saida').value;
        const btn = document.getElementById('btn-gerar-automatico');
        const loading = document.getElementById('loading-ia');

        if (!exemplo.trim()) {
            alert('Por favor, cole um texto de exemplo primeiro.');
            return;
        }

        btn.disabled = true;
        btn.classList.add('opacity-50');
        loading.classList.remove('hidden');

        try {
            const response = await fetch(ROTA_GERAR_AUTO, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ exemplo_saida: exemplo })
            });

            const data = await response.json();

            if (data.sucesso) {
                document.getElementById('prompt_sistema').value = data.prompt_sistema;
                document.getElementById('json_schema_saida').value = data.json_schema_saida;
                alert('✨ Prompt e Schema gerados com sucesso!');
            } else {
                alert('Erro: ' + (data.erro || 'Falha desconhecida'));
            }

        } catch (error) {
            console.error(error);
            alert('Erro na comunicação com o servidor.');
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-50');
            loading.classList.add('hidden');
        }
    }

    async function salvarTemplate() {
        const id = document.getElementById('template_id').value;
        const nome = document.getElementById('nome_template').value;
        const prompt = document.getElementById('prompt_sistema').value;
        const schema = document.getElementById('json_schema_saida').value;

        // Aqui você faria o POST para ROTA_SALVAR
        // Lembre-se de tratar Create vs Update dependendo se o ID existe
        
        alert('Implementar o POST para salvar: ' + nome);
        // location.reload(); // Recarregar após salvar
    }
</script>
@endsection