<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Exibe o formulário de login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Processa a tentativa de login.
     */
    public function authenticate(Request $request)
    {
        // 1. Validação
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
            'senha' => ['required', 'string', 'min:6', 'max:50'],
        ]);

        // 2. Mapeia os campos (Auth espera 'password', tu usas 'senha')
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('senha'),
        ];

        // 3. Tenta logar
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Verifica se está ativo
            $user = Auth::user();
            if ($user->ativo != 1) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Esta conta está desativada. Contacte o administrador.',
                ]);
            }

            // Lógica de Redirecionamento Baseada no Nível
            if ($user->nivel_acesso == User::NIVEL_CADASTRO) {
                return redirect()->route('produtos_dashboard.index');
            }

            // Padrão para os demais níveis (Mestre, Admin, Usuário)
            return redirect()->route('dashboard');
        }

        // Falha
        throw ValidationException::withMessages([
            'email' => 'Email ou senha inválidos.',
        ]);
    }

    /**
     * ESTA É A FUNÇÃO QUE ESTAVA A FALTAR!
     * Faz o logout e destrói a sessão.
     */
    public function logout(Request $request)
    {
        Auth::logout(); // Desloga o user do Guard

        $request->session()->invalidate(); // Invalida a sessão PHP
        $request->session()->regenerateToken(); // Gera novo token CSRF (segurança)

        return redirect()->route('login'); // Manda de volta para o login
    }
}
