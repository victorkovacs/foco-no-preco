<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfiguracaoController extends Controller
{
    public function index()
    {
        $configs = DB::table('configuracoes_sistema')->get();
        return view('admin.configuracoes.index', ['configs' => $configs]);
    }

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
}
