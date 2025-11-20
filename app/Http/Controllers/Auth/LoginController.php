<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Exibe o formulário de login.
     */
    public function showLoginForm()
    {
        // Retorna a View Blade que criamos: resources/views/auth/login.blade.php
        return view('auth.login'); 
    }

    /**
     * Processa a tentativa de login com validação profissional.
     */
    public function authenticate(Request $request)
    {
        // 1. VALIDAÇÃO PROFISSIONAL
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'], 
            'senha' => ['required', 'string', 'min:6', 'max:50'], 
        ]);
        
        // 2. Mapeamento das Credenciais para o padrão do Laravel (Auth::attempt espera 'password')
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('senha'), 
        ];

        // 3. TENTATIVA DE AUTENTICAÇÃO
        if (Auth::attempt($credentials)) {
            
            $request->session()->regenerate(); 
            
            // Lógica de STATUS ATIVO
            $user = Auth::user();
            if ($user->ativo != 1) {
                Auth::logout(); 
                throw ValidationException::withMessages([
                    'email' => 'Esta conta está desativada. Contacte o administrador.',
                ]);
            }
            
            // SUCESSO!
            return redirect()->intended('/index'); 
        }

        // FALHA
        throw ValidationException::withMessages([
            'email' => 'Email ou senha inválidos.',
        ]);
    }
    
    /**
     * Faz o logout seguro do usuário e redireciona.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redireciona para a página de login
        return redirect()->route('login'); 
    }
}