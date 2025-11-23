@extends('layouts.app')

@section('title', 'Gestão de Utilizadores')

@section('content')
<div x-data="usersData" class="w-full max-w-5xl mx-auto bg-white shadow-xl rounded-2xl p-6 md:p-8 relative">
    
    <div x-show="toast.visible" 
         x-transition.duration.300ms
         class="fixed top-5 right-5 z-50 flex items-center w-full max-w-md p-4 bg-white rounded-lg shadow-lg border-l-4"
         :class="toast.type === 'success' ? 'border-green-500 text-green-600' : 'border-red-500 text-red-600'"
         style="display: none;">
        <div class="mr-3 flex-shrink-0">
            <i data-lucide="check-circle" x-show="toast.type === 'success'" class="w-6 h-6"></i>
            <i data-lucide="alert-circle" x-show="toast.type === 'error'" class="w-6 h-6"></i>
        </div>
        <div class="text-sm font-medium break-words" x-text="toast.message"></div>
    </div>

    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Gestão de Utilizadores</h1>
            <p class="text-gray-500 text-sm mt-1">Adicione e gerencie o acesso da sua equipe.</p>
        </div>
        <button type="button" @click="openModal()" class="bg-primary-dark hover:bg-primary-darker text-white px-5 py-2.5 rounded-lg shadow-md flex items-center transition-transform hover:scale-105">
            <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i> Novo Utilizador
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 min-h-[200px]">
        
        <div x-show="isLoading" class="col-span-full flex justify-center items-center py-10">
            <i data-lucide="loader-2" class="w-10 h-10 animate-spin text-primary-dark"></i>
        </div>

        <div x-show="!isLoading && users.length === 0" class="col-span-full text-center text-gray-500 py-10" style="display: none;">
            <div class="flex flex-col items-center">
                <i data-lucide="users" class="w-12 h-12 text-gray-300 mb-3"></i>
                <p>Nenhum utilizador encontrado.</p>
            </div>
        </div>

        <template x-for="user in users" :key="user.id">
            <div class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-shadow flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                              :class="user.nivel_acesso == 1 ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                              x-text="user.nivel_acesso == 1 ? 'Admin' : (user.nivel_acesso == 2 ? 'User' : 'Cadastro')">
                        </span>
                        <span class="flex items-center text-xs font-medium px-2 py-1 rounded-full"
                              :class="user.ativo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="user.ativo ? 'bg-green-500' : 'bg-red-500'"></span>
                            <span x-text="user.ativo ? 'Ativo' : 'Inativo'"></span>
                        </span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800 truncate" :title="user.email" x-text="user.email"></h4>
                    <p class="text-xs text-gray-400 mt-1 font-mono truncate">
                        API: <span x-text="user.api_key ? '...' + user.api_key.slice(-8) : 'N/A'"></span>
                    </p>
                </div>
                <div class="mt-5 pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" @click="openModal(user)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                        <i data-lucide="edit-2" class="w-5 h-5"></i>
                    </button>
                    <button type="button" @click="deleteUser(user.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="modalOpen" style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
         x-transition.opacity>
        
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl transform transition-all"
             x-transition.scale
             @click.outside="modalOpen = false">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                <h3 class="text-xl font-bold text-gray-800" x-text="isEdit ? 'Editar Utilizador' : 'Novo Utilizador'"></h3>
                <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form @submit.prevent="saveUser" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" x-model="form.email" required class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" x-model="form.senha" :required="!isEdit" 
                           class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none" 
                           :placeholder="isEdit ? 'Deixe em branco para manter' : '********'">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nível</label>
                        <select x-model="form.nivel_acesso" class="w-full p-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-dark outline-none">
                            <option value="1">Administrador</option>
                            <option value="2">Utilizador</option>
                            <option value="3">Cadastro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select x-model="form.ativo" class="w-full p-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-dark outline-none">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="pt-2 border-t border-gray-100">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Chave de API</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="form.api_key" readonly class="flex-1 p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-xs font-mono text-gray-600">
                        <button type="button" @click="generateKey" class="p-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 border border-gray-300" title="Gerar Nova">
                            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="modalOpen = false" class="px-5 py-2.5 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary-dark hover:bg-primary-darker text-white rounded-lg font-medium shadow-md transition-colors flex items-center" :disabled="isSaving">
                        <i x-show="isSaving" data-lucide="loader-2" class="animate-spin w-4 h-4 mr-2"></i>
                        <span x-text="isSaving ? 'Salvando...' : 'Salvar'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('usersData', () => ({
            users: [],
            isLoading: true,
            isSaving: false,
            modalOpen: false,
            isEdit: false,
            form: { id: null, email: '', senha: '', nivel_acesso: 2, ativo: 1, api_key: '' },
            toast: { visible: false, message: '', type: 'success' },

            init() {
                this.fetchUsers();
            },

            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.visible = true;
                
                // Garante que os ícones do toast sejam renderizados
                this.$nextTick(() => lucide.createIcons());
                setTimeout(() => { this.toast.visible = false }, 4000);
            },

            // Função central para cabeçalhos da API (evita repetição)
            getHeaders() {
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json', // Importante para o Laravel não redirecionar em erro
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                };
            },

            fetchUsers() {
                this.isLoading = true;
                // headers adicionados para garantir resposta JSON
                fetch("{{ route('users.list') }}", { headers: { 'Accept': 'application/json' } })
                    .then(res => {
                        if(!res.ok) throw new Error('Erro HTTP: ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        this.users = data;
                        this.$nextTick(() => lucide.createIcons());
                    })
                    .catch(error => {
                        console.error(error);
                        this.showToast('Não foi possível carregar a lista de utilizadores.', 'error');
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });
            },

            openModal(user = null) {
                this.isEdit = !!user;
                if (user) {
                    // Clona o usuário para o form e limpa a senha para não enviar hash antigo
                    this.form = { ...user, senha: '' };
                } else {
                    this.form = { id: null, email: '', senha: '', nivel_acesso: 2, ativo: 1, api_key: '' };
                }
                this.modalOpen = true;
            },

            generateKey() {
                const array = new Uint8Array(24);
                window.crypto.getRandomValues(array);
                this.form.api_key = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
            },

            saveUser() {
                this.isSaving = true;
                const url = this.isEdit ? `{{ url('/users') }}/${this.form.id}` : `{{ route('users.store') }}`;
                const method = this.isEdit ? 'PUT' : 'POST';

                fetch(url, {
                    method: method,
                    headers: this.getHeaders(),
                    body: JSON.stringify(this.form)
                })
                .then(async res => {
                    const data = await res.json();
                    
                    // Se a resposta não for OK (ex: 422 Erro de Validação)
                    if (!res.ok) {
                        // Se houver erros de validação (padrão Laravel)
                        if (res.status === 422 && data.errors) {
                            // Junta as mensagens de erro em uma única string
                            throw new Error(Object.values(data.errors).flat().join(' '));
                        }
                        throw new Error(data.message || 'Erro ao salvar.');
                    }
                    return data;
                })
                .then(data => {
                    this.modalOpen = false;
                    this.fetchUsers(); // Recarrega a lista
                    this.showToast(data.message, 'success');
                })
                .catch(error => {
                    console.error(error);
                    this.showToast(error.message, 'error');
                })
                .finally(() => {
                    this.isSaving = false;
                });
            },

            deleteUser(id) {
                if (!confirm('Tem certeza que deseja remover este utilizador?')) return;
                
                fetch(`{{ url('/users') }}/${id}`, {
                    method: 'DELETE',
                    headers: this.getHeaders()
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Erro ao excluir');
                    return data;
                })
                .then(data => {
                    this.fetchUsers();
                    this.showToast(data.message, 'success');
                })
                .catch(error => {
                    this.showToast(error.message, 'error');
                });
            }
        }));
    });
</script>
@endsection