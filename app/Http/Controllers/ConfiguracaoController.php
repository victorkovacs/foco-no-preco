<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Organizacao;

class ConfiguracaoController extends Controller
{
    /**
     * Exibe a tela de configurações (Rotinas e API Key).
     */
    public function index()
    {
        // 1. Carrega as configurações de rotina (existente)
        $configs = DB::table('configuracoes_sistema')->get();

        // 2. Carrega a organização do usuário logado para exibir a API Key (NOVO)
        // O usuário logado deve ter um id_organizacao
        $organizacao = Organizacao::find(Auth::user()->id_organizacao);

        return view('admin.configuracoes.index', [
            'configs' => $configs,
            'organizacao' => $organizacao
        ]);
    }

    /**
     * Atualiza as rotinas (existente).
     */
    public function update(Request $request)
    {
        $dados = $request->input('configs');
        if ($dados) {
            foreach ($dados as $chave => $valor) {
                DB::table('configuracoes_sistema')
                    ->where('chave', $chave)
                    ->update(['valor' => $valor, 'updated_at' => now()]);
            }
        }
        return redirect()->back()->with('success', 'Rotinas atualizadas!');
    }

    /**
     * Gera uma nova Chave de API para a Organização (NOVO).
     */
    public function gerarTokenOrganizacao(Request $request)
    {
        $user = Auth::user();

        // Verifica se é Administrador (Nível 1)
        if ($user->nivel_acesso != 1) {
            return response()->json(['success' => false, 'message' => 'Apenas administradores podem gerar chaves.'], 403);
        }

        try {
            $organizacao = Organizacao::find($user->id_organizacao);

            if (!$organizacao) {
                return response()->json(['success' => false, 'message' => 'Organização não encontrada.'], 404);
            }

            // Gera token seguro de 64 caracteres
            $organizacao->api_key = Str::random(64);
            $organizacao->save();

            return response()->json([
                'success' => true,
                'message' => 'Nova Chave de API gerada com sucesso!',
                'api_key' => $organizacao->api_key
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao salvar no banco.'], 500);
        }
    }
}
