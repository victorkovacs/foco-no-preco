@extends('layouts.app')

@section('title', 'Configurações do Sistema')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-5xl">
    
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
            <i data-lucide="settings" class="w-8 h-8 mr-3 text-primary-dark"></i>
            Configurações
        </h1>
    </div>

    {{-- REMOVIDO: O bloco @if(session('success')) que causava duplicação --}}

    {{-- 1. SEÇÃO: CHAVE DE API DA ORGANIZAÇÃO --}}
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8 relative" x-data="apiKeyManager()">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i data-lucide="key" class="w-5 h-5 mr-2 text-blue-600"></i>
                    Chave de API da Organização
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Chave única para integração de scripts externos (robôs e scrapers).
                    <span class="text-red-600 font-bold block sm:inline">Mantenha esta chave em segredo!</span>
                </p>
            </div>
            {{-- Botão que abre o Modal --}}
            <button type="button" @click="askConfirmation" :disabled="isLoading" 
                    class="group flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium border border-blue-200">
                <i data-lucide="refresh-cw" class="w-4 h-4 mr-2 transition-transform group-hover:rotate-180" :class="{'animate-spin': isLoading}"></i>
                Gerar Nova Chave
            </button>
        </div>

        <div class="relative z-0">
            <div class="flex items-center border border-gray-300 rounded-lg bg-gray-50 p-1 pl-4 focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                <code class="flex-1 font-mono text-sm text-gray-700 break-all py-2" x-text="apiKey || 'Nenhuma chave gerada. Clique em gerar.'"></code>
                
                <div class="flex border-l border-gray-300 pl-1">
                    <button type="button" @click="copyToClipboard" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-md transition-all m-1" title="Copiar para área de transferência">
                        <i data-lucide="copy" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            {{-- Feedback de Cópia --}}
            <div x-show="showFeedback" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute top-full mt-2 right-0 bg-green-600 text-white text-xs font-bold px-3 py-1.5 rounded shadow-lg flex items-center z-10">
                <i data-lucide="check" class="w-3 h-3 mr-1"></i> Copiado!
            </div>
        </div>

        {{-- MODAL CUSTOMIZADO DE CONFIRMAÇÃO --}}
        <div x-show="showConfirmModal" style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
             x-transition.opacity>
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all"
                 x-transition.scale
                 @click.outside="showConfirmModal = false">
                
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                    </div>
                    
                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">Atenção Crítica</h3>
                    
                    <p class="text-sm text-center text-gray-500 mb-6">
                        Ao gerar uma nova chave, <strong>a chave anterior deixará de funcionar imediatamente</strong>.
                        <br><br>
                        Todos os seus robôs e scripts externos precisarão ser atualizados manualmente com a nova chave.
                    </p>

                    <div class="flex gap-3 justify-center">
                        <button type="button" @click="showConfirmModal = false" 
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors">
                            Cancelar
                        </button>
                        <button type="button" @click="proceedWithGeneration" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium shadow-md transition-colors flex items-center">
                            Sim, Gerar Nova Chave
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. SEÇÃO: AGENDAMENTO DE ROTINAS --}}
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <h2 class="text-xl font-bold text-gray-800 flex items-center mb-6">
            <i data-lucide="calendar-clock" class="w-5 h-5 mr-2 text-purple-600"></i>
            Agendamento de Rotinas
        </h2>

        <form action="{{ route('admin.configuracoes.update') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($configs as $config)
                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 hover:border-blue-300 transition-colors">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="{{ $config->chave }}">
                            {{ $config->descricao }}
                        </label>
                        
                        <div class="flex items-center">
                            @if($config->tipo == 'time')
                                <input type="time" 
                                       name="configs[{{ $config->chave }}]" 
                                       value="{{ $config->valor }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-700">
                            @elseif($config->tipo == 'number')
                                <input type="number" 
                                       name="configs[{{ $config->chave }}]" 
                                       value="{{ $config->valor }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-700">
                                <span class="ml-2 text-gray-500 text-sm font-medium">horas</span>
                            @else
                                <input type="text" 
                                       name="configs[{{ $config->chave }}]" 
                                       value="{{ $config->valor }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-700">
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-2 font-mono">ID: {{ $config->chave }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" class="bg-primary-dark hover:bg-opacity-90 text-white font-bold py-2.5 px-6 rounded-lg shadow hover:shadow-lg transition-all flex items-center">
                    <i data-lucide="save" class="w-4 h-4 mr-2"></i> Salvar Agendamentos
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function apiKeyManager() {
        return {
            apiKey: '{{ $organizacao->api_key ?? "" }}',
            isLoading: false,
            showFeedback: false,
            showConfirmModal: false,

            askConfirmation() {
                this.showConfirmModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            proceedWithGeneration() {
                this.showConfirmModal = false;
                this.isLoading = true;
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch("{{ route('admin.configuracoes.gerar_token') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.apiKey = data.api_key;
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erro de conexão ao tentar gerar a chave.');
                })
                .finally(() => {
                    this.isLoading = false;
                });
            },

            copyToClipboard() {
                if (!this.apiKey) return;
                
                navigator.clipboard.writeText(this.apiKey).then(() => {
                    this.showFeedback = true;
                    setTimeout(() => this.showFeedback = false, 2500);
                }).catch(() => {
                    alert('Não foi possível copiar automaticamente. Selecione e copie manualmente.');
                });
            }
        }
    }
</script>
@endsection