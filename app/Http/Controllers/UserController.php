<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Exibe a página de gestão de usuários.
     */
    public function index()
    {
        // MELHOR PRÁTICA: Autorização centralizada via Gate ou Policy.
        // Certifique-se de definir o Gate 'admin-access' no AppServiceProvider.
        Gate::authorize('admin-access');

        return view('users.index');
    }

    /**
     * Retorna a lista de usuários da organização (JSON).
     */
    public function list()
    {
        Gate::authorize('admin-access');

        try {
            $id_organizacao = Auth::user()->id_organizacao;

            $users = User::where('id_organizacao', $id_organizacao)
                ->orderBy('nivel_acesso', 'asc') // Admins primeiro
                ->orderBy('email', 'asc')
                ->get()
                // SEGURANÇA: Oculta dados sensíveis na resposta da API
                ->makeHidden(['senha_hash', 'remember_token', 'api_key']);

            return response()->json($users);
        } catch (\Exception $e) {
            // Em produção, evite retornar $e->getMessage() completo para não expor detalhes da infra
            return response()->json(['error' => 'Erro ao listar usuários.'], 500);
        }
    }

    /**
     * Cria um novo usuário.
     */
    public function store(Request $request)
    {
        Gate::authorize('admin-access');

        // Validação com Best Practices (Rule::unique resolve a tabela correta)
        $request->validate([
            'email' => ['required', 'email', Rule::unique(User::class)],
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

            // SEGURANÇA: Gera chave apenas se solicitado e nunca aceita input do usuário
            if ($request->boolean('gerar_api_key')) {
                $user->api_key = Str::random(64);
            }

            $user->save();

            return response()->json(['success' => true, 'message' => 'Utilizador criado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao salvar usuário.'], 500);
        }
    }

    /**
     * Atualiza um usuário existente.
     */
    public function update(Request $request, $id)
    {
        Gate::authorize('admin-access');

        // Garante que o user pertence à organização (Mitigação de IDOR)
        $user = User::where('id_organizacao', Auth::user()->id_organizacao)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'email' => ['required', 'email', Rule::unique(User::class)->ignore($user->id)],
            'nivel_acesso' => 'required|integer',
            'ativo' => 'required|boolean'
        ]);

        try {
            $user->email = $request->email;
            $user->nivel_acesso = $request->nivel_acesso;
            $user->ativo = $request->ativo;

            // Atualiza senha apenas se fornecida
            if ($request->filled('senha')) {
                $user->senha_hash = Hash::make($request->senha);
            }

            // SEGURANÇA: Permite regenerar a API Key, mas não definí-la manualmente
            if ($request->boolean('regenerar_api_key')) {
                $user->api_key = Str::random(64);
            } elseif ($request->boolean('remover_api_key')) {
                $user->api_key = null;
            }

            $user->save();

            return response()->json(['success' => true, 'message' => 'Utilizador atualizado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar usuário.'], 500);
        }
    }

    /**
     * Remove um usuário.
     */
    public function destroy($id)
    {
        Gate::authorize('admin-access');

        if ($id == Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Não pode excluir o seu próprio usuário.'], 400);
        }

        try {
            // Mitigação de IDOR
            $user = User::where('id_organizacao', Auth::user()->id_organizacao)
                ->where('id', $id)
                ->firstOrFail();

            $user->delete();

            return response()->json(['success' => true, 'message' => 'Utilizador removido.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao excluir usuário.'], 500);
        }
    }
}
