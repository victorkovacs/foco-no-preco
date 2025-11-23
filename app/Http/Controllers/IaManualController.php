<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Necessário para queries diretas

class IaManualController extends Controller
{
    public function index()
    {
        return view('ia_manual.index');
    }

    public function process(Request $request)
    {
        // Validação
        $request->validate([
            'skus' => 'required|array',
            'skus.*' => 'string'
        ]);

        $id_organizacao = Auth::user()->id_organizacao;
        $skus = $request->skus;

        // 1. Buscar os IDs dos produtos baseados nos SKUs
        $produtosIds = Produto::where('id_organizacao', $id_organizacao)
            ->whereIn('SKU', $skus)
            ->pluck('ID');

        if ($produtosIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum dos SKUs informados foi encontrado.'
            ], 404);
        }

        // 2. LÓGICA FIEL AO ORIGINAL: 
        // Apagar os alvos antigos na tabela 'AlvosMonitoramento' para esses produtos.
        // Isso força a IA a buscar novos concorrentes do zero.
        DB::table('AlvosMonitoramento')
            ->where('id_organizacao', $id_organizacao)
            ->whereIn('ID_Produto', $produtosIds)
            ->delete();

        // 3. Resetar o status da IA na tabela 'Produtos' (ia_processado = 0)
        // Isso coloca o produto na fila de processamento.
        $affected = Produto::where('id_organizacao', $id_organizacao)
            ->whereIn('ID', $produtosIds)
            ->update(['ia_processado' => 0]);

        return response()->json([
            'success' => true,
            'message' => "Sucesso! $affected produtos foram limpos e enviados para reprocessamento da IA."
        ]);
    }
}
