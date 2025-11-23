<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DetalheController extends Controller
{
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $filtro = $request->query('filtro', 'todos'); // melhor, media, acima, sem_concorrencia...

        // 1. LÓGICA SQL (Reutilizada do Dashboard para consistência)
        // Traz todos os produtos e calcula o status de preço de cada um
        $sql = "
            SELECT 
                p.SKU, 
                p.Nome,
                p.LinkMeuSite,
                p.PrecoVenda as meu_preco,
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
                    MIN(CASE WHEN origem = 'concorrente' THEN preco END) as min_concorrente,
                    AVG(CASE WHEN origem = 'concorrente' THEN preco END) as avg_concorrente
                FROM (
                    -- Preços dos Concorrentes (Mais recentes)
                    SELECT 
                        c.sku,
                        c.preco,
                        'concorrente' as origem,
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

        // Executa a query
        $todosProdutos = DB::select($sql, [$id_organizacao, $id_organizacao]);

        // 2. FILTRAGEM PHP
        // Como a lógica é complexa, filtramos o array resultante
        $produtosFiltrados = collect($todosProdutos)->filter(function ($prod) use ($filtro) {

            // Filtros de Preço
            if (in_array($filtro, ['melhor', 'media', 'acima'])) {
                return $prod->status_preco === $filtro;
            }

            // Filtro: Sem Concorrência (Monitorados mas sem dados hoje)
            if ($filtro === 'sem_concorrencia') {
                // Se min_concorrente for nulo, significa que não achou nada hoje
                return is_null($prod->min_concorrente);
            }

            return true; // 'todos'
        });

        // Título dinâmico para a página
        $titulos = [
            'melhor' => 'Produtos com Melhor Preço',
            'media'  => 'Produtos na Média de Mercado',
            'acima'  => 'Produtos Acima da Média',
            'sem_concorrencia' => 'Sem Dados de Concorrência Hoje',
            'todos'  => 'Todos os Produtos Monitorados'
        ];
        $tituloPagina = $titulos[$filtro] ?? 'Detalhes';

        return view('detalhe_unificado.index', [
            'produtos' => $produtosFiltrados,
            'titulo' => $tituloPagina,
            'filtro' => $filtro
        ]);
    }
}
