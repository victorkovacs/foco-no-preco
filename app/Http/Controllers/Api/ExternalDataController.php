<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Organizacao;
use Carbon\Carbon;

class ExternalDataController extends Controller
{
    /**
     * Retorna a lista de concorrentes coletados no dia.
     * Endpoint protegido por X-API-KEY da Organização.
     */
    public function getConcorrentesHoje(Request $request)
    {
        // 1. Validação de Segurança (API Key)
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Acesso não autorizado. Chave de API ausente.'
            ], 401);
        }

        // Busca a organização dona dessa chave
        $organizacao = Organizacao::where('api_key', $apiKey)->first();

        if (!$organizacao) {
            return response()->json([
                'success' => false,
                'error' => 'Acesso não autorizado. Chave inválida.'
            ], 403);
        }

        // 2. Definição da Data (Padrão: Hoje, ou passada via ?date=Y-m-d)
        try {
            $dataPesquisa = $request->input('date')
                ? Carbon::parse($request->input('date'))->format('Y-m-d')
                : Carbon::now()->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Formato de data inválido. Use YYYY-MM-DD.'
            ], 400);
        }

        try {
            // 3. Consulta ao Banco (Query Builder Otimizado)
            $dados = DB::table('concorrentes as c')
                // Join com Produtos para pegar dados de custo e referência
                ->join('produtos as p', function ($join) {
                    $join->on('c.sku', '=', 'p.sku')
                        ->on('c.id_organizacao', '=', 'p.id_organizacao');
                })
                ->select(
                    'c.sku',
                    'c.titulo',
                    'c.preco',
                    'c.link',
                    'c.marketplace',
                    'c.data_extracao',
                    'c.diff_percentage',
                    'c.vendedor_nome',
                    'p.custo',
                    'p.taxa',
                    'p.status_preco'
                )
                // Filtros de Segurança e Negócio
                ->where('c.id_organizacao', $organizacao->id_organizacao) // Garante isolamento entre empresas
                ->whereDate('c.data_extracao', $dataPesquisa)
                ->where('c.ID_Vendedor', '!=', 5) // Exclui vendedor interno/ignorado (regra do script original)
                ->whereNotNull('c.preco')
                ->where('c.preco', '>', 0)
                // Ordenação para facilitar leitura
                ->orderBy('c.sku')
                ->orderBy('c.preco', 'asc')
                ->get();

            // 4. Montagem da Resposta JSON
            return response()->json([
                'success' => true,
                'organization' => $organizacao->nome_empresa ?? 'Organização Identificada', // Ajustado para nome_empresa (campo da tabela)
                'date' => $dataPesquisa,
                'count' => $dados->count(),
                'data' => $dados
            ]);
        } catch (\Exception $e) {
            // Log do erro real no servidor (storage/logs/laravel.log) para debug seguro
            Log::error("Erro na API de Concorrentes (Org: {$organizacao->id_organizacao}): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Ocorreu um erro interno ao processar sua solicitação.'
            ], 500);
        }
    }
}
