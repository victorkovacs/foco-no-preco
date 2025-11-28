@extends('layouts.app')

@section('title', 'Gestão de Utilizadores')

@section('content')
<div x-data="usersData" class="w-full max-w-5xl mx-auto bg-white shadow-xl rounded-2xl p-6 md:p-8 relative">
    
    {{-- Toast de Notificação --}}
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

    {{-- Cabeçalho --}}
    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Gestão de Utilizadores</h1>
            <p class="text-gray-500 text-sm mt-1">Adicione e gerencie o acesso da sua equipe.</p>
        </div>
        <button type="button" @click="openModal()" class="bg-primary-dark hover:bg-primary-darker text-white px-5 py-2.5 rounded-lg shadow-md flex items-center transition-transform hover:scale-105">
            <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i> Novo Utilizador
        </button>
    </div>

    {{-- Lista de Cards --}}
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
                        {{-- LABEL DE NÍVEL AJUSTADA --}}
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                              :class="{
                                  'bg-purple-100 text-purple-700': user.nivel_acesso == 1, 
                                  'bg-indigo-100 text-indigo-700': user.nivel_acesso == 2, 
                                  'bg-yellow-100 text-yellow-700': user.nivel_acesso == 3, 
                                  'bg-blue-100 text-blue-700': user.nivel_acesso == 4
                              }"
                              x-text="user.nivel_acesso == 1 ? 'Mestre' : (user.nivel_acesso == 2 ? 'Admin' : (user.nivel_acesso == 3 ? 'Cadastro' : 'Usuário'))">
                        </span>

                        <span class="flex items-center text-xs font-medium px-2 py-1 rounded-full"
                              :class="user.ativo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="user.ativo ? 'bg-green-500' : 'bg-red-500'"></span>
                            <span x-text="user.ativo ? 'Ativo' : 'Inativo'"></span>
                        </span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-800 truncate" :title="user.email" x-text="user.email"></h4>
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

    {{-- Modal --}}
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
                
                {{-- Campo Senha com Visualizar --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" 
                               x-model="form.senha" 
                               :required="!isEdit" 
                               class="w-full p-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none transition-colors" 
                               :placeholder="isEdit ? 'Deixe em branco para manter' : '********'">
                        
                        <button type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-md hover:bg-gray-100 transition-colors"
                                tabindex="-1">
                            <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                            <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5" style="display: none;"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nível</label>
                        {{-- DROPDOWN ATUALIZADO PARA OS NOVOS NÍVEIS --}}
                        <select x-model="form.nivel_acesso" class="w-full p-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-dark outline-none">
                            <option value="2">Administrador</option>
                            <option value="3">Cadastro</option>
                            <option value="4">Usuário</option>
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
            showPassword: false,
            // Padrão atualizado para '4' (Usuário) e ativo '1'
            form: { id: null, email: '', senha: '', nivel_acesso: '4', ativo: '1' },
            toast: { visible: false, message: '', type: 'success' },

            init() {
                this.fetchUsers();
            },

            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.visible = true;
                this.$nextTick(() => lucide.createIcons());
                setTimeout(() => { this.toast.visible = false }, 4000);
            },

            getHeaders() {
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                };
            },

            fetchUsers() {
                this.isLoading = true;
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
                this.showPassword = false;

                if (user) {
                    this.form = { 
                        ...user, 
                        senha: '', 
                        ativo: user.ativo ? '1' : '0',
                        nivel_acesso: String(user.nivel_acesso)
                    };
                } else {
                    // Reset para novo usuário com nível 4 por padrão
                    this.form = { id: null, email: '', senha: '', nivel_acesso: '4', ativo: '1' };
                }
                
                this.modalOpen = true;
                this.$nextTick(() => lucide.createIcons());
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
                    
                    if (!res.ok) {
                        if (res.status === 422 && data.errors) {
                            throw new Error(Object.values(data.errors).flat().join(' '));
                        }
                        throw new Error(data.message || 'Erro ao salvar.');
                    }
                    return data;
                })
                .then(data => {
                    this.modalOpen = false;
                    this.fetchUsers();
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