<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        // Segurança: Apenas Admin
        if (Auth::user()->nivel_acesso != 1) {
            return redirect()->route('dashboard')->with('error', 'Acesso restrito.');
        }
        return view('users.index');
    }

    public function list()
    {
        try {
            $id_organizacao = Auth::user()->id_organizacao;

            $users = User::where('id_organizacao', $id_organizacao)
                ->orderBy('nivel_acesso', 'asc') // Admins primeiro
                ->orderBy('email', 'asc')
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao listar: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // Validação
        $request->validate([
            'email' => 'required|email|unique:Usuarios,email',
            'senha' => 'required|min:6',
            'nivel_acesso' => 'required|integer',
            'ativo' => 'required|boolean'
        ]);

        try {
            $user = new User();
            $user->id_organizacao = Auth::user()->id_organizacao;
            $user->email = $request->email;
            $user->senha_hash = Hash::make($request->senha);
            $user->nivel_acesso = $request->nivel_acesso;
            $user->ativo = $request->ativo;
            $user->api_key = $request->api_key; // Pode ser null

            $user->save();

            return response()->json(['success' => true, 'message' => 'Utilizador criado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Garante que o user pertence à organização
        $user = User::where('id_organizacao', Auth::user()->id_organizacao)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'email' => 'required|email|unique:Usuarios,email,' . $id,
            'nivel_acesso' => 'required|integer',
            'ativo' => 'required|boolean'
        ]);

        try {
            $user->email = $request->email;
            $user->nivel_acesso = $request->nivel_acesso;
            $user->ativo = $request->ativo;
            $user->api_key = $request->api_key;

            if ($request->filled('senha')) {
                $user->senha_hash = Hash::make($request->senha);
            }

            $user->save();

            return response()->json(['success' => true, 'message' => 'Utilizador atualizado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        if ($id == Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Não pode excluir o seu próprio usuário.'], 400);
        }

        try {
            $user = User::where('id_organizacao', Auth::user()->id_organizacao)
                ->where('id', $id)
                ->firstOrFail();

            $user->delete();

            return response()->json(['success' => true, 'message' => 'Utilizador removido.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()], 500);
        }
    }
}
