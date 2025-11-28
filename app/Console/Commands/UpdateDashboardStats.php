<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateDashboardStats extends Command
{
    /**
     * O nome e a assinatura do comando de console.
     *
     * @var string
     */
    protected $signature = 'dashboard:update';

    /**
     * A descrição do comando de console.
     *
     * @var string
     */
    protected $description = 'Gera o cache JSON com as estatísticas do Dashboard (concorrentes e preços)';

    /**
     * Executa o comando de console.
     */
    public function handle()
    {
        $this->info('Iniciando geração de cache do dashboard...');

        // Busca todas as Organizações Ativas
        $organizacoes = DB::table('Organizacoes')
            ->where('ativa', 1)
            ->select('id_organizacao', 'nome_empresa')
            ->get();

        foreach ($organizacoes as $org) {
            $this->info("------------------------------------------------");
            $this->info("Processando: {$org->nome_empresa} (ID: {$org->id_organizacao})");

            try {
                $cacheData = $this->generateDataForOrganization($org);

                // Garante que o diretório existe
                if (!Storage::exists('cache')) {
                    Storage::makeDirectory('cache');
                }

                $filename = "cache/stats_org_{$org->id_organizacao}.json";
                Storage::put($filename, json_encode($cacheData));

                $this->info(" [OK] Cache salvo em: {$filename}");
            } catch (\Exception $e) {
                $this->error(" [ERRO] Falha na Org {$org->id_organizacao}: " . $e->getMessage());
            }
        }

        $this->info('------------------------------------------------');
        $this->info('Processo finalizado.');
    }

    private function generateDataForOrganization($org)
    {
        $id_organizacao = $org->id_organizacao;

        // Limpeza do nome da empresa para melhorar o "match" no banco
        $termoBusca = $org->nome_empresa;
        $removiveis = [' Matriz', ' Filial', ' Ltda', ' S/A', ' S.A.', ' ME', ' EPP', ' Inc'];
        $termoBusca = str_ireplace($removiveis, '', $termoBusca);
        $termoBusca = trim($termoBusca);

        $this->comment(" -> Buscando vendedor com termo: '%{$termoBusca}%'");

        // --- PARTE 1: LÓGICA DE PREÇOS (Comparativo de Hoje) ---
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
                    SELECT 
                        c.sku,
                        c.preco,
                        -- Identifica meu preço vs concorrente
                        CASE 
                            WHEN v.NomeVendedor LIKE ? THEN 'meu' 
                            ELSE 'concorrente' 
                        END as origem,
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
                GROUP BY sku
            ) AS pc ON p.SKU = pc.sku
            WHERE p.ativo = 1 AND p.id_organizacao = ?
        ";

        $produtosCategorizados = DB::select($sqlPrecos, ["%{$termoBusca}%", $id_organizacao, $id_organizacao]);

        // Contadores dos Termômetros de Preço
        $dadosPreco = ['melhor' => 0, 'media' => 0, 'acima' => 0, 'total_monitorado' => 0];

        foreach ($produtosCategorizados as $prod) {
            if ($prod->meu_preco > 0) {
                $dadosPreco['total_monitorado']++;
                if (isset($dadosPreco[$prod->status_preco])) {
                    $dadosPreco[$prod->status_preco]++;
                }
            }
        }

        // --- PARTE 2: STATUS GERAIS (Baseado no Cadastro) ---

        // Total de Produtos Ativos no Cadastro
        $totalAtivos = DB::table('Produtos')
            ->where('id_organizacao', $id_organizacao)
            ->where('ativo', 1)
            ->count();

        // Produtos que têm a flag de concorrentes ativada
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

        // --- PARTE 3: PESQUISAS HOJE (Temporal) ---

        // Quantos produtos distintos tiveram preços coletados hoje
        $pesquisadosHoje = DB::table('concorrentes as c')
            ->join('Produtos as p', function ($join) {
                $join->on('c.sku', '=', 'p.SKU')
                    ->on('c.id_organizacao', '=', 'p.id_organizacao');
            })
            ->where('c.id_organizacao', $id_organizacao)
            ->where('p.id_organizacao', $id_organizacao)
            ->where('p.ativo', 1)
            ->whereDate('c.data_extracao', '>=', now()->today())
            ->distinct('c.sku')
            ->count('c.sku');

        $statusPesquisa = [
            'pesquisados_hoje' => $pesquisadosHoje,
            'sem_pesquisa_hoje' => $totalAtivos - $pesquisadosHoje, // Base é o total de ativos
            'total' => $totalAtivos
        ];

        // --- PARTE 4: COMPETIDORES (Baseado no Cadastro/Vínculo) ---
        // Mostra quem eu monitoro, independente de ter preço hoje
        $sqlCompetidores = "
            SELECT 
                v.NomeVendedor as nome,
                COUNT(DISTINCT p.SKU) as count_sku
            FROM AlvosMonitoramento a
            JOIN Produtos p ON a.ID_Produto = p.ID
            JOIN links_externos l ON a.id_link_externo = l.id
            JOIN global_links gl ON l.global_link_id = gl.id
            JOIN Vendedores v ON gl.ID_Vendedor = v.ID_Vendedor
            WHERE a.id_organizacao = ?
              AND a.ativo = 1
              AND p.ativo = 1
              AND v.Ativo = 1
              AND v.NomeVendedor NOT LIKE ? -- Exclui minha própria loja
            GROUP BY v.NomeVendedor
            ORDER BY count_sku DESC
            LIMIT 20
        ";

        $competidoresRaw = DB::select($sqlCompetidores, [$id_organizacao, "%{$termoBusca}%"]);
        $dadosCompetidores = array_map(fn($i) => ['nome' => $i->nome, 'count' => $i->count_sku], $competidoresRaw);

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
