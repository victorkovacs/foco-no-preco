@extends('layouts.app')

@section('title', 'Gestão de Utilizadores')

@section('content')
<div class="w-full max-w-5xl mx-auto bg-white shadow-xl rounded-2xl p-6 md:p-8">
    
    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Gestão de Utilizadores</h1>
            <p class="text-gray-500 text-sm mt-1">Adicione e gerencie o acesso da sua equipe.</p>
        </div>
        <button id="btn-novo-usuario" class="bg-primary-dark hover:bg-primary-darker text-white px-5 py-2.5 rounded-lg shadow-md flex items-center transition-all transform hover:scale-105">
            <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i> Novo Utilizador
        </button>
    </div>

    <div id="lista-usuarios" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="col-span-full text-center py-10 text-gray-500">Carregando utilizadores...</div>
    </div>

</div>

<div id="modal-usuario" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 transition-transform duration-300" id="modal-content">
        
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
            <h3 class="text-xl font-bold text-gray-800" id="modal-titulo">Novo Utilizador</h3>
            <button id="btn-fechar-modal" class="text-gray-400 hover:text-red-500 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <form id="form-usuario" class="p-6 space-y-5">
            <input type="hidden" id="usuario_id"> <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email de Acesso</label>
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                    <input type="email" id="email" required class="pl-10 w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none transition-shadow" placeholder="colaborador@empresa.com">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                    <input type="password" id="senha" class="pl-10 w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-dark outline-none transition-shadow" placeholder="********">
                </div>
                <p class="text-xs text-gray-500 mt-1 hidden" id="aviso-senha">Deixe em branco para manter a senha atual.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nível de Acesso</label>
                    <select id="nivel_acesso" class="w-full p-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-dark outline-none">
                        <option value="1">Administrador</option>
                        <option value="2">Utilizador</option>
                        <option value="3">Cadastro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="ativo" class="w-full p-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-dark outline-none">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-1">Chave de API (Opcional)</label>
                <div class="flex gap-2">
                    <input type="text" id="api_key" readonly class="flex-1 p-2.5 bg-gray-50 border border-gray-300 rounded-lg text-xs font-mono text-gray-600 focus:outline-none">
                    <button type="button" id="btn-gerar-key" class="p-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors" title="Gerar Nova Chave">
                        <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" id="btn-cancelar" class="px-5 py-2.5 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">Cancelar</button>
                <button type="submit" id="btn-salvar" class="px-5 py-2.5 bg-primary-dark hover:bg-primary-darker text-white rounded-lg font-medium shadow-md transition-all transform active:scale-95">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modal-usuario');
        const modalContent = document.getElementById('modal-content');
        const form = document.getElementById('form-usuario');
        const listaUsuarios = document.getElementById('lista-usuarios');
        
        // Elementos do Form
        const inputId = document.getElementById('usuario_id');
        const inputEmail = document.getElementById('email');
        const inputSenha = document.getElementById('senha');
        const selectNivel = document.getElementById('nivel_acesso');
        const selectAtivo = document.getElementById('ativo');
        const inputApiKey = document.getElementById('api_key');
        const avisoSenha = document.getElementById('aviso-senha');

        // --- FUNÇÕES DO MODAL ---
        function abrirModal(usuario = null) {
            if (usuario) {
                // Modo Edição
                document.getElementById('modal-titulo').innerText = 'Editar Utilizador';
                inputId.value = usuario.id;
                inputEmail.value = usuario.email;
                inputSenha.value = '';
                inputSenha.placeholder = 'Deixe em branco para manter';
                inputSenha.required = false;
                avisoSenha.classList.remove('hidden');
                selectNivel.value = usuario.nivel_acesso;
                selectAtivo.value = usuario.ativo;
                inputApiKey.value = usuario.api_key || '';
            } else {
                // Modo Criação
                document.getElementById('modal-titulo').innerText = 'Novo Utilizador';
                form.reset();
                inputId.value = '';
                inputSenha.required = true;
                inputSenha.placeholder = '********';
                avisoSenha.classList.add('hidden');
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        function fecharModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // --- GERAR API KEY ---
        document.getElementById('btn-gerar-key').addEventListener('click', () => {
            const array = new Uint8Array(24);
            window.crypto.getRandomValues(array);
            inputApiKey.value = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
        });

        // --- CRUD (AJAX) ---
        
        // 1. Listar
        function carregarUsuarios() {
            listaUsuarios.innerHTML = '<div class="col-span-full text-center py-10"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-primary-dark"></i></div>';
            lucide.createIcons();

            fetch("{{ route('users.list') }}")
                .then(res => res.json())
                .then(data => {
                    listaUsuarios.innerHTML = '';
                    if (data.length === 0) {
                        listaUsuarios.innerHTML = '<div class="col-span-full text-center text-gray-500">Nenhum utilizador encontrado.</div>';
                        return;
                    }

                    data.forEach(u => {
                        const nivelLabel = u.nivel_acesso == 1 ? 'Administrador' : (u.nivel_acesso == 2 ? 'Utilizador' : 'Cadastro');
                        const nivelCor = u.nivel_acesso == 1 ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
                        const statusCor = u.ativo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                        const statusLabel = u.ativo ? 'Ativo' : 'Inativo';

                        const card = document.createElement('div');
                        card.className = 'bg-white border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-shadow flex flex-col justify-between';
                        card.innerHTML = `
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide ${nivelCor}">${nivelLabel}</span>
                                    <span class="flex items-center text-xs font-medium ${statusCor} px-2 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full ${u.ativo ? 'bg-green-500' : 'bg-red-500'} mr-1.5"></span>
                                        ${statusLabel}
                                    </span>
                                </div>
                                <h4 class="text-lg font-bold text-gray-800 truncate" title="${u.email}">${u.email}</h4>
                                <p class="text-xs text-gray-400 mt-1 font-mono truncate">API: ${u.api_key ? '...' + u.api_key.substr(-8) : 'N/A'}</p>
                            </div>
                            <div class="mt-5 pt-4 border-t border-gray-100 flex justify-end gap-2">
                                <button class="btn-editar p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <i data-lucide="edit-2" class="w-5 h-5"></i>
                                </button>
                                <button class="btn-excluir p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </div>
                        `;

                        // Eventos dos botões
                        card.querySelector('.btn-editar').addEventListener('click', () => abrirModal(u));
                        card.querySelector('.btn-excluir').addEventListener('click', () => excluirUsuario(u.id));

                        listaUsuarios.appendChild(card);
                    });
                    lucide.createIcons();
                });
        }

        // 2. Salvar (Criar ou Editar)
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = inputId.value;
            const url = id ? `{{ url('/users') }}/${id}` : `{{ route('users.store') }}`;
            const method = id ? 'PUT' : 'POST';

            const payload = {
                email: inputEmail.value,
                nivel_acesso: selectNivel.value,
                ativo: selectAtivo.value,
                api_key: inputApiKey.value,
                senha: inputSenha.value
            };

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fecharModal();
                    carregarUsuarios();
                    alert(data.message); // Pode trocar por um Toast mais bonito depois
                } else {
                    alert('Erro: ' + JSON.stringify(data.message || data.errors));
                }
            })
            .catch(err => alert('Erro de conexão.'));
        });

        // 3. Excluir
        function excluirUsuario(id) {
            if (!confirm('Tem certeza que deseja excluir este utilizador? Esta ação é irreversível.')) return;

            fetch(`{{ url('/users') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    carregarUsuarios();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }

        // Listeners Globais
        document.getElementById('btn-novo-usuario').addEventListener('click', () => abrirModal());
        document.getElementById('btn-fechar-modal').addEventListener('click', fecharModal);
        document.getElementById('btn-cancelar').addEventListener('click', fecharModal);

        // Iniciar
        carregarUsuarios();
    });
</script>
@endsection