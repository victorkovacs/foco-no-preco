@extends('layouts.app')

@section('title', 'Alterar Senha')

@section('content')
<div class="w-full flex items-center justify-center py-12 px-4">

    <div class="w-full max-w-xl mx-auto bg-white shadow-xl rounded-2xl p-8 md:p-10">
        
        <h1 class="text-3xl font-bold text-gray-900 mb-6 border-b pb-4">
            Alterar Senha
        </h1>

        <div id="status-success" class="hidden p-4 rounded-lg mb-4 text-sm bg-green-100 text-green-700 flex items-center">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
            <span id="msg-success"></span>
        </div>
        
        <div id="status-error" class="hidden p-4 rounded-lg mb-4 text-sm bg-red-100 text-red-700 flex items-center">
            <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
            <span id="msg-error"></span>
        </div>

        <form id="form-mudar-senha" class="space-y-5">
            @csrf

            <div>
                <label for="senha_antiga" class="block text-sm font-medium text-gray-700 mb-1">Senha Antiga</label>
                <div class="relative">
                    <input type="password" id="senha_antiga" name="senha_antiga" required 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                           placeholder="Digite sua senha atual">
                    <button type="button" onclick="togglePassword('senha_antiga', this)" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 transition-colors">
                        <i data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="nova_senha" class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                <div class="relative">
                    <input type="password" id="nova_senha" name="nova_senha" required minlength="6"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                           placeholder="Mínimo de 6 caracteres">
                    <button type="button" onclick="togglePassword('nova_senha', this)" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 transition-colors">
                        <i data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="confirmar_senha" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha</label>
                <div class="relative">
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                           placeholder="Repita a nova senha">
                    <button type="button" onclick="togglePassword('confirmar_senha', this)" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 transition-colors">
                        <i data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="px-6 py-3 text-gray-600 hover:bg-gray-100 rounded-lg font-medium transition-colors">
                    Cancelar
                </a>
                <button type="submit" id="btn-salvar-senha" 
                        class="bg-primary-dark hover:bg-primary-darker text-white px-8 py-3 rounded-lg font-semibold shadow-md transition-all transform active:scale-95 flex items-center">
                    <span>Salvar Nova Senha</span>
                    <i data-lucide="save" class="ml-2 w-4 h-4"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Renderiza ícones
    lucide.createIcons();

    // Toggle Visibilidade Senha
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i'); // O ícone dentro do botão
        
        if (input.type === 'password') {
            input.type = 'text';
            // Troca ícone para "eye-off" (se biblioteca suportar, senão muda cor)
            btn.classList.add('text-blue-600'); 
        } else {
            input.type = 'password';
            btn.classList.remove('text-blue-600');
        }
    }

    // Lógica de Envio
    document.getElementById('form-mudar-senha').addEventListener('submit', async function(e) {
        e.preventDefault();

        const senhaAntiga = document.getElementById('senha_antiga').value;
        const novaSenha = document.getElementById('nova_senha').value;
        const confirmarSenha = document.getElementById('confirmar_senha').value;

        // Validação simples no front
        if (novaSenha !== confirmarSenha) {
            mostrarErro('A nova senha e a confirmação não coincidem.');
            return;
        }

        const btnSalvar = document.getElementById('btn-salvar-senha');
        const textoOriginal = btnSalvar.innerHTML;
        
        // Estado de Carregamento
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i data-lucide="loader-2" class="animate-spin w-5 h-5 mr-2"></i> Processando...';
        lucide.createIcons();
        ocultarMensagens();

        try {
            const response = await fetch("{{ route('profile.password.update') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    senha_antiga: senhaAntiga,
                    nova_senha: novaSenha,
                    confirmar_senha: confirmarSenha
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                mostrarSucesso(result.message);
                document.getElementById('form-mudar-senha').reset();
                
                // LOGOUT APÓS 3 SEGUNDOS (Igual ao original)
                // Como o logout no Laravel é POST, vamos submeter o form de logout do layout
                setTimeout(() => {
                    // Procura o form de logout no layout e submete
                    const logoutForm = document.querySelector('form[action="{{ route("logout") }}"]');
                    if(logoutForm) {
                        logoutForm.submit();
                    } else {
                        // Fallback se não achar o form
                        window.location.reload();
                    }
                }, 3000);

            } else {
                // Mostra erro vindo do servidor ou validação
                const msg = result.error || result.message || 'Erro ao alterar senha.';
                mostrarErro(msg);
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = textoOriginal;
            }

        } catch (error) {
            mostrarErro('Erro de comunicação: ' + error.message);
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = textoOriginal;
        }
    });

    function mostrarErro(msg) {
        const el = document.getElementById('status-error');
        document.getElementById('msg-error').textContent = msg;
        el.classList.remove('hidden');
    }

    function mostrarSucesso(msg) {
        const el = document.getElementById('status-success');
        document.getElementById('msg-success').textContent = msg;
        el.classList.remove('hidden');
    }

    function ocultarMensagens() {
        document.getElementById('status-success').classList.add('hidden');
        document.getElementById('status-error').classList.add('hidden');
    }
</script>
@endsection