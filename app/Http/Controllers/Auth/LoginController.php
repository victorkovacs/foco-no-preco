<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm(){
        return view('auth.login');
    }


    public function authenticate(Request $request){
    
        $request->validade([
            'email' => ['required', 'email'],
            'senha' => ['required'],
        ]);

        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('senha'),
        ];

        if (Auth::attempt($credentials)){

            $user = Auth::user();
            if ($user->ativo != 1){
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'Esta conta está desativada. Contacte o administrador.'
                ]);
            }

            return redirect()->intended('/index');
        }
    
        throw ValidationException::withMessages([
            'email' => 'Email ou senhas inválidos.',
        ]);
    }
}
