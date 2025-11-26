<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Exporta o histórico de preços da tabela concorrentes para CSV.
     */
    public function exportConcorrentes(Request $request)
    {
        // 1. Segurança: Pega ID da Organização
        $orgId = Auth::user()->id_organizacao;

        // 2. Filtros de Data (Essenciais para performance em tabelas particionadas)
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        // 3. Configura o nome do arquivo
        $fileName = 'historico_precos_' . date('Y-m-d_H-i') . '.csv';

        // 4. Cria a resposta em Stream (Sem carregar tudo na RAM)
        return response()->streamDownload(function () use ($orgId, $startDate, $endDate) {

            // Abre o output stream do PHP
            $handle = fopen('php://output', 'w');

            // Adiciona o BOM para o Excel abrir UTF-8 corretamente (opcional, mas recomendado)
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Cabeçalhos do CSV
            fputcsv($handle, [
                'Data Extração',
                'SKU',
                'Produto (Link Externo)', // Se tiver nome no link externo
                'Vendedor',
                'Preço Coletado',
                'ID Link'
            ], ';'); // Ponto e vírgula é melhor para Excel no Brasil

            // 5. Query Otimizada com Chunk
            // Usamos DB::table para performance bruta, evitando hidratar Models pesados
            $query = DB::table('concorrentes')
                ->join('Vendedores', 'concorrentes.ID_Vendedor', '=', 'Vendedores.ID_Vendedor')
                ->leftJoin('links_externos', 'concorrentes.id_link_externo', '=', 'links_externos.id')
                ->select(
                    'concorrentes.data_extracao',
                    'concorrentes.sku',
                    'links_externos.nome as nome_produto_concorrente', // Ou links_externos.link
                    'Vendedores.NomeVendedor',
                    'concorrentes.preco',
                    'concorrentes.id_link_externo'
                )
                ->where('concorrentes.id_organizacao', $orgId) // FILTRO DE SEGURANÇA
                ->whereBetween('concorrentes.data_extracao', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ])
                ->orderBy('concorrentes.data_extracao', 'desc');

            // Processa em blocos de 1000 registros para economizar memória
            $query->chunk(1000, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->data_extracao,
                        $row->sku,
                        $row->nome_produto_concorrente ?? 'N/A',
                        $row->NomeVendedor,
                        number_format($row->preco, 2, ',', '.'), // Formato Brasileiro
                        $row->id_link_externo
                    ], ';');
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
