<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Organizacao;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * [NOVO] Exporta os dados da tela de Detalhes do Dashboard (Filtrados).
     * Gera um CSV exatamente com o que o usuário está vendo na tela (Melhor, Média, Acima, etc).
     */
    public function exportDashboardDetalhes(Request $request)
    {
        $user = Auth::user();
        $id_organizacao = $user->id_organizacao;

        // Busca nome da organização para identificar "Meu Preço"
        $org = Organizacao::find($id_organizacao);
        $nomeMinhaLoja = $org ? $org->nome_empresa : 'Minha Loja';

        $filtro = $request->query('filtro', 'todos');

        // 1. QUERY SQL (Idêntica à do Dashboard/Detalhes para consistência)
        $sql = "
            SELECT 
                p.SKU, 
                p.Nome,
                p.LinkMeuSite,
                pc.meu_preco,
                pc.min_concorrente,
                pc.avg_concorrente,
                CASE
                    WHEN pc.meu_preco IS NOT NULL AND pc.meu_preco < pc.min_concorrente THEN 'melhor'
                    WHEN pc.meu_preco IS NOT NULL AND pc.meu_preco >= pc.min_concorrente AND pc.meu_preco <= (pc.avg_concorrente * 1.10) THEN 'media' 
                    WHEN pc.meu_preco IS NOT NULL AND pc.meu_preco > (pc.avg_concorrente * 1.10) THEN 'acima'
                    ELSE 'indefinido'
                END as status_preco
            FROM Produtos p
            LEFT JOIN (
                SELECT 
                    sku,
                    MAX(CASE WHEN origem = 'meu' THEN preco END) as meu_preco,
                    MIN(CASE WHEN origem = 'concorrente' THEN preco END) as min_concorrente,
                    AVG(CASE WHEN origem = 'concorrente' THEN preco END) as avg_concorrente
                FROM (
                    SELECT 
                        c.sku,
                        c.preco,
                        -- Identificação dinâmica pelo nome da empresa
                        CASE 
                            WHEN v.NomeVendedor LIKE ? THEN 'meu'
                            ELSE 'concorrente' 
                        END as origem,
                        ROW_NUMBER() OVER(PARTITION BY c.sku, c.ID_Vendedor ORDER BY c.data_extracao DESC) as rn
                    FROM concorrentes c
                    JOIN Vendedores v ON c.ID_Vendedor = v.ID_Vendedor
                    WHERE c.data_extracao >= CURDATE() 
                      AND c.data_extracao < (CURDATE() + INTERVAL 1 DAY) 
                      AND c.id_organizacao = ?  
                      AND v.Ativo = 1
                ) AS UltimosPrecos
                WHERE rn = 1
                GROUP BY sku
            ) AS pc ON p.SKU = pc.sku
            WHERE p.ativo = 1 AND p.id_organizacao = ?
        ";

        $todosProdutos = DB::select($sql, ["%{$nomeMinhaLoja}%", $id_organizacao, $id_organizacao]);

        // 2. FILTRAGEM (Aplica o mesmo filtro da tela)
        $produtosFiltrados = collect($todosProdutos)->filter(function ($prod) use ($filtro) {
            if (in_array($filtro, ['melhor', 'media', 'acima'])) {
                return $prod->status_preco === $filtro;
            }
            if ($filtro === 'sem_concorrencia') {
                return is_null($prod->min_concorrente);
            }
            return true;
        });

        // 3. GERAÇÃO DO ARQUIVO CSV
        $fileName = 'dashboard_' . $filtro . '_' . date('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () use ($produtosFiltrados) {
            $handle = fopen('php://output', 'w');

            // Adiciona BOM para Excel abrir acentos corretamente
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Cabeçalho do CSV
            fputcsv($handle, [
                'SKU',
                'Produto',
                'Meu Preço',
                'Melhor Concorrente',
                'Média Mercado',
                'Diferença R$',
                'Diferença %',
                'Status',
                'Link Meu Site'
            ], ';');

            foreach ($produtosFiltrados as $prod) {
                // Cálculos de diferença
                $diff = 0;
                $diffPercent = 0;
                if ($prod->meu_preco && $prod->min_concorrente) {
                    $diff = $prod->meu_preco - $prod->min_concorrente;
                    $diffPercent = ($prod->min_concorrente > 0) ? ($diff / $prod->min_concorrente) * 100 : 0;
                }

                // Escreve a linha
                fputcsv($handle, [
                    $this->sanitizeForCsv($prod->SKU),
                    $this->sanitizeForCsv($prod->Nome),
                    number_format($prod->meu_preco ?? 0, 2, ',', '.'),
                    number_format($prod->min_concorrente ?? 0, 2, ',', '.'),
                    number_format($prod->avg_concorrente ?? 0, 2, ',', '.'),
                    number_format($diff, 2, ',', '.'),
                    number_format($diffPercent, 2, ',', '.') . '%',
                    strtoupper($prod->status_preco),
                    $prod->LinkMeuSite
                ], ';');
            }
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * [ANTIGO] Exporta o histórico bruto de preços da tabela concorrentes.
     * Mantido para compatibilidade com outras telas.
     */
    public function exportConcorrentes(Request $request)
    {
        $orgId = Auth::user()->id_organizacao;
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $fileName = 'historico_precos_' . date('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () use ($orgId, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Data Extração',
                'SKU',
                'Produto (Link Externo)',
                'Vendedor',
                'Preço Coletado',
                'ID Link'
            ], ';');

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
                ->where('concorrentes.id_organizacao', $orgId)
                ->whereBetween('concorrentes.data_extracao', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ])
                ->orderBy('concorrentes.data_extracao', 'desc');

            $query->chunk(1000, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->data_extracao,
                        $this->sanitizeForCsv($row->sku),
                        $this->sanitizeForCsv($row->nome_produto_concorrente ?? 'N/A'),
                        $this->sanitizeForCsv($row->NomeVendedor),
                        number_format($row->preco, 2, ',', '.'),
                        $row->id_link_externo
                    ], ';');
                }
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Previne CSV Injection (Spreadsheet Injection).
     * Se o valor começar com caracteres de fórmula (=, +, -, @), adiciona uma aspa simples.
     */
    private function sanitizeForCsv($value)
    {
        if (is_string($value) && in_array(substr($value, 0, 1), ['=', '+', '-', '@'])) {
            return "'" . $value;
        }
        return $value;
    }
}
