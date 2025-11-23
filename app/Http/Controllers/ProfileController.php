<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // 1. Exibir o formulário
    public function edit()
    {
        return view('profile.change_password');
    }

    // 2. Processar a alteração (AJAX)
    public function update(Request $request)
    {
        $request->validate([
            'senha_antiga' => 'required',
            'nova_senha' => 'required|min:6|different:senha_antiga',
            'confirmar_senha' => 'required|same:nova_senha'
        ]);

        $user = Auth::user();

        // Verifica se a senha antiga está correta
        if (!Hash::check($request->senha_antiga, $user->senha_hash)) {
            return response()->json([
                'success' => false,
                'error' => 'A senha antiga está incorreta.'
            ], 400);
        }

        // Atualiza a senha
        // Nota: O teu Model User já sabe que a coluna é 'senha_hash' e usa 'getAuthPassword'
        // Mas para o update direto, usamos o nome da coluna explicitamente ou o Model
        $user->senha_hash = Hash::make($request->nova_senha);
        $user->save();

        // Opcional: Logout automático (como no original) é feito pelo JS no frontend
        // Aqui apenas retornamos o sucesso.

        return response()->json([
            'success' => true,
            'message' => 'Senha alterada com sucesso! Você será desconectado por segurança.'
        ]);
    }
}
