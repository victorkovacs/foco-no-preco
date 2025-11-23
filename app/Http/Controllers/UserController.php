<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // 1. Carregar a Página
    public function index()
    {
        // Apenas admins (Nível 1) podem ver esta tela
        if (Auth::user()->nivel_acesso > 1) {
            return redirect()->route('dashboard')->with('error', 'Acesso não autorizado.');
        }

        return view('users.index');
    }

    // 2. API: Listar Utilizadores
    public function list()
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // Busca todos os utilizadores da MESMA organização
        $users = User::where('id_organizacao', $id_organizacao)
            ->orderBy('nivel_acesso') // Admins primeiro
            ->orderBy('email')
            ->get();

        return response()->json($users);
    }

    // 3. API: Criar Utilizador
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:Usuarios,email', // Nome da tabela é 'Usuarios'
            'senha' => 'required|min:6',
            'nivel_acesso' => 'required|integer',
            'api_key' => 'nullable|unique:Usuarios,api_key'
        ]);

        $user = new User();
        $user->id_organizacao = Auth::user()->id_organizacao;
        $user->email = $request->email;
        $user->senha_hash = Hash::make($request->senha); // Criptografa a senha
        $user->nivel_acesso = $request->nivel_acesso;
        $user->ativo = $request->ativo ?? 1;
        $user->api_key = $request->api_key;

        $user->save();

        return response()->json(['success' => true, 'message' => 'Utilizador criado com sucesso!']);
    }

    // 4. API: Atualizar Utilizador
    public function update(Request $request, $id)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $user = User::where('id_organizacao', $id_organizacao)->where('id', $id)->firstOrFail();

        $request->validate([
            'email' => 'required|email|unique:Usuarios,email,' . $id, // Ignora o próprio ID na verificação
            'nivel_acesso' => 'required|integer',
            'api_key' => 'nullable|unique:Usuarios,api_key,' . $id
        ]);

        $user->email = $request->email;
        $user->nivel_acesso = $request->nivel_acesso;
        $user->ativo = $request->ativo ?? 1;
        $user->api_key = $request->api_key;

        // Só atualiza a senha se for enviada uma nova
        if ($request->filled('senha')) {
            $user->senha_hash = Hash::make($request->senha);
        }

        $user->save();

        return response()->json(['success' => true, 'message' => 'Utilizador atualizado com sucesso!']);
    }

    // 5. API: Excluir Utilizador
    public function destroy($id)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // Impede que o utilizador se apague a si mesmo
        if ($id == Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Você não pode excluir o seu próprio utilizador.'], 403);
        }

        $user = User::where('id_organizacao', $id_organizacao)->where('id', $id)->firstOrFail();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'Utilizador excluído.']);
    }
}
