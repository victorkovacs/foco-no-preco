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
     * Inclui proteção contra CSV Injection.
     */
    public function exportConcorrentes(Request $request)
    {
        // 1. Segurança: Pega ID da Organização
        $orgId = Auth::user()->id_organizacao;

        // 2. Filtros de Data
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        // 3. Configura o nome do arquivo
        $fileName = 'historico_precos_' . date('Y-m-d_H-i') . '.csv';

        // 4. Cria a resposta em Stream (Sem carregar tudo na RAM)
        return response()->streamDownload(function () use ($orgId, $startDate, $endDate) {

            // Abre o output stream do PHP
            $handle = fopen('php://output', 'w');

            // Adiciona o BOM para o Excel abrir UTF-8 corretamente
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Cabeçalhos do CSV
            fputcsv($handle, [
                'Data Extração',
                'SKU',
                'Produto (Link Externo)',
                'Vendedor',
                'Preço Coletado',
                'ID Link'
            ], ';'); // Ponto e vírgula para Excel Brasil

            // 5. Query Otimizada com DB::table
            $query = DB::table('concorrentes')
                ->join('Vendedores', 'concorrentes.ID_Vendedor', '=', 'Vendedores.ID_Vendedor')
                ->leftJoin('links_externos', 'concorrentes.id_link_externo', '=', 'links_externos.id')
                ->select(
                    'concorrentes.data_extracao',
                    'concorrentes.sku',
                    'links_externos.nome as nome_produto_concorrente',
                    'Vendedores.NomeVendedor',
                    'concorrentes.preco',
                    'concorrentes.id_link_externo'
                )
                ->where('concorrentes.id_organizacao', $orgId) // FILTRO DE SEGURANÇA (Multi-Tenant)
                ->whereBetween('concorrentes.data_extracao', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ])
                ->orderBy('concorrentes.data_extracao', 'desc');

            // Processa em blocos para economizar memória
            $query->chunk(1000, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->data_extracao,
                        // Sanitização obrigatória para evitar fórmulas maliciosas
                        $this->sanitizeForCsv($row->sku),
                        $this->sanitizeForCsv($row->nome_produto_concorrente ?? 'N/A'),
                        $this->sanitizeForCsv($row->NomeVendedor),
                        number_format($row->preco, 2, ',', '.'),
                        $row->id_link_externo
                    ], ';');
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Previne CSV Injection (Spreadsheet Injection).
     * Se o valor começar com caracteres de fórmula (=, +, -, @), adiciona uma aspa simples para forçar texto.
     */
    private function sanitizeForCsv($value)
    {
        if (is_string($value) && in_array(substr($value, 0, 1), ['=', '+', '-', '@'])) {
            return "'" . $value;
        }
        return $value;
    }
}
