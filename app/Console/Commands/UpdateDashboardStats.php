<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateDashboardStats extends Command
{
    protected $signature = 'dashboard:update';
    protected $description = 'Gera o cache JSON com as estatísticas do Dashboard (concorrentes e preços)';

    public function handle()
    {
        $this->info('Iniciando geração de cache do dashboard...');

        // Busca todas as Organizações Ativas
        $organizacoes = DB::table('Organizacoes')->where('ativa', 1)->pluck('id_organizacao');

        foreach ($organizacoes as $id_organizacao) {
            $this->info("Processando Organização ID: {$id_organizacao}...");
            
            try {
                $cacheData = $this->generateDataForOrganization($id_organizacao);
                
                // Garante que o diretório existe
                if (!Storage::exists('cache')) {
                    Storage::makeDirectory('cache');
                }
                
                $filename = "cache/stats_org_{$id_organizacao}.json";
                Storage::put($filename, json_encode($cacheData));
                
                $this->info("Cache salvo: {$filename}");

            } catch (\Exception $e) {
                $this->error("Erro na Org {$id_organizacao}: " . $e->getMessage());
            }
        }

        $this->info('Geração de cache concluída com sucesso!');
    }

    private function generateDataForOrganization($id_organizacao)
    {
        // --- PARTE 1: LÓGICA DE PREÇOS ---
        // Ajustada para a tabela 'concorrentes' e 'Produtos' do teu SQL
        $sqlPrecos = "
            SELECT 
                p.SKU, 
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
            JOIN (
                SELECT 
                    sku,
                    MAX(CASE WHEN origem = 'meu' THEN preco END) as meu_preco,
                    MIN(CASE WHEN origem = 'concorrente' THEN preco END) as min_concorrente,
                    AVG(CASE WHEN origem = 'concorrente' THEN preco END) as avg_concorrente
                FROM (
                    -- Meus Preços
                    SELECT 
                        p.SKU as sku,
                        p.PrecoVenda as preco,
                        'meu' as origem,
                        1 as rn
                    FROM Produtos p
                    WHERE p.id_organizacao = ? AND p.ativo = 1

                    UNION ALL

                    -- Preços dos Concorrentes (Tabela 'concorrentes')
                    SELECT 
                        UltimosPrecos.sku,
                        UltimosPrecos.preco,
                        'concorrente' as origem,
                        1 as rn
                    FROM (
                        SELECT 
                            c.sku,
                            c.preco,
                            -- Ordena por data para pegar o mais recente
                            ROW_NUMBER() OVER(PARTITION BY c.sku, c.ID_Vendedor ORDER BY c.data_extracao DESC) as rn
                        FROM concorrentes c
                        JOIN Produtos p ON c.sku = p.SKU
                        JOIN Vendedores v ON c.ID_Vendedor = v.ID_Vendedor
                        WHERE c.data_extracao >= CURDATE() 
                          AND c.data_extracao < (CURDATE() + INTERVAL 1 DAY) 
                          AND c.id_organizacao = ?
                          AND v.Ativo = 1
                          AND p.ativo = 1
                    ) AS UltimosPrecos
                    WHERE rn = 1
                ) AS PrecosAtuais
                GROUP BY sku
            ) AS pc ON p.SKU = pc.sku
            WHERE p.ativo = 1 AND p.id_organizacao = ? AND pc.min_concorrente IS NOT NULL
        ";

        $produtosCategorizados = DB::select($sqlPrecos, [$id_organizacao, $id_organizacao, $id_organizacao]);

        // Contagem dos Termômetros
        $dadosPreco = [
            'melhor' => 0,
            'media' => 0,
            'acima' => 0,
            'total_monitorado' => count($produtosCategorizados)
        ];

        foreach ($produtosCategorizados as $prod) {
            if (isset($dadosPreco[$prod->status_preco])) {
                $dadosPreco[$prod->status_preco]++;
            }
        }

        // --- PARTE 2: DADOS DE CONCORRÊNCIA ---
        $totalAtivos = DB::table('Produtos')->where('id_organizacao', $id_organizacao)->where('ativo', 1)->count();
        $comConcorrentes = DB::table('Produtos')
            ->where('id_organizacao', $id_organizacao)
            ->where('ativo', 1)
            ->where('EncontrouConcorrentes', 1)
            ->count();
        
        $statusConcorrencia = [
            'com' => $comConcorrentes,
            'sem' => $totalAtivos - $comConcorrentes,
            'total' => $totalAtivos
        ];

        // --- PARTE 3: DADOS DE PESQUISA (HOJE) ---
        // Nota: A tabela Produtos do teu SQL não tem 'UltimaAtualizacao'.
        // Vou assumir que podemos usar a tabela 'concorrentes' para saber quem foi pesquisado hoje.
        $pesquisadosHoje = DB::table('concorrentes')
            ->where('id_organizacao', $id_organizacao)
            ->whereDate('data_extracao', '>=', now()->today())
            ->distinct('sku')
            ->count('sku');
        
        $statusPesquisa = [
            'pesquisados_hoje' => $pesquisadosHoje,
            'sem_pesquisa_hoje' => $comConcorrentes - $pesquisadosHoje, // Aproximação
            'total' => $comConcorrentes
        ];

        // --- PARTE 4: COMPETIDORES (Gráfico de Pizza) ---
        $sqlCompetidores = "
            SELECT 
                v.NomeVendedor as nome,
                COUNT(DISTINCT c.sku) as count_sku
            FROM concorrentes c
            JOIN Produtos p ON c.sku = p.SKU
            JOIN Vendedores v ON c.ID_Vendedor = v.ID_Vendedor
            WHERE c.id_organizacao = ?
              AND p.id_organizacao = ?
              AND c.data_extracao >= CURDATE()
              AND p.ativo = 1
              AND v.Ativo = 1
            GROUP BY v.NomeVendedor
            ORDER BY count_sku DESC
        ";
        
        $competidoresRaw = DB::select($sqlCompetidores, [$id_organizacao, $id_organizacao]);
        $dadosCompetidores = array_map(function($item) {
            return ['nome' => $item->nome, 'count' => $item->count_sku];
        }, $competidoresRaw);

        return [
            'dados_preco' => $dadosPreco,
            'status_concorrencia' => $statusConcorrencia,
            'status_pesquisa' => $statusPesquisa,
            'dados_competidores' => $dadosCompetidores,
            'total_com_concorrentes' => $comConcorrentes,
            'generated_at' => now()->toDateTimeString()
        ];
    }
}