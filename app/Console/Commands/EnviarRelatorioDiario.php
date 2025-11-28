<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\RelatorioConcorrentesMail;
use App\Models\Organizacao;
use App\Models\User;
use Carbon\Carbon;

class EnviarRelatorioDiario extends Command
{
    /**
     * Nome do comando para rodar no terminal: php artisan relatorio:diario
     */
    protected $signature = 'relatorio:diario {organizacao_id?}';

    protected $description = 'Gera e envia o relatório CSV diário para os administradores da organização';

    public function handle()
    {
        $this->info('Iniciando rotina de envio de relatórios...');

        // 1. Define quais organizações processar (Uma específica ou todas ativas)
        $orgId = $this->argument('organizacao_id');

        $query = Organizacao::where('ativa', 1);
        if ($orgId) {
            $query->where('id_organizacao', $orgId);
        }
        $organizacoes = $query->get();

        foreach ($organizacoes as $org) {
            $this->processarOrganizacao($org);
        }

        $this->info('Rotina finalizada com sucesso.');
    }

    private function processarOrganizacao($org)
    {
        $this->info("Processando Organização: {$org->nome_empresa} (ID: {$org->id_organizacao})...");

        // 2. Filtra os Destinatários (CORREÇÃO: APENAS NÍVEL ADMIN = 2)
        $destinatarios = User::where('id_organizacao', $org->id_organizacao)
            ->where('ativo', 1)
            ->where('nivel_acesso', User::NIVEL_ADMIN) // Restrição estrita ao nível 2
            ->pluck('email')
            ->toArray();

        if (empty($destinatarios)) {
            $this->warn(" -> Nenhum usuário 'Admin' (Nível 2) ativo encontrado. Pulando envio.");
            return;
        }

        // 3. Busca os Dados no Banco
        $hoje = Carbon::today()->format('Y-m-d');
        $nomeMinhaLoja = $org->nome_empresa;
        $removiveis = [' Matriz', ' Filial', ' Ltda', ' S/A', ' S.A.', ' ME', ' EPP', ' Inc'];
        $termoBusca = trim(str_ireplace($removiveis, '', $nomeMinhaLoja));

        $dados = DB::table('concorrentes as c')
            // Join com Produtos
            ->join('Produtos as p', function ($join) {
                $join->on('c.sku', '=', 'p.SKU')
                    ->on('c.id_organizacao', '=', 'p.id_organizacao');
            })
            // Join com Vendedores
            ->join('Vendedores as v', 'c.ID_Vendedor', '=', 'v.ID_Vendedor')
            // Join para pegar o Link (Via tabela normalizada)
            ->leftJoin('links_externos as le', 'c.id_link_externo', '=', 'le.id')
            ->leftJoin('global_links as gl', 'le.global_link_id', '=', 'gl.id')

            ->select(
                'c.sku',
                'p.Nome as nome_produto',
                'v.NomeVendedor as concorrente',
                'c.preco',
                'gl.link',
                'c.data_extracao'
            )
            ->where('c.id_organizacao', $org->id_organizacao)
            ->whereDate('c.data_extracao', $hoje)
            ->where('v.NomeVendedor', 'NOT LIKE', "%{$termoBusca}%") // Exclui minha própria loja
            ->whereNotNull('c.preco')
            ->where('c.preco', '>', 0)
            ->orderBy('c.sku')
            ->orderBy('c.preco', 'asc')
            ->get();

        if ($dados->isEmpty()) {
            $this->warn(" -> Nenhum dado coletado hoje ({$hoje}). E-mail não enviado.");
            return;
        }

        // 4. Gera o CSV em Memória
        $csvHandle = fopen('php://memory', 'r+');
        // Adiciona BOM para o Excel abrir acentos corretamente (UTF-8)
        fprintf($csvHandle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho do CSV
        fputcsv($csvHandle, ['SKU', 'Produto', 'Concorrente', 'Preço R$', 'Link', 'Data'], ';');

        foreach ($dados as $row) {
            fputcsv($csvHandle, [
                $row->sku,
                $row->nome_produto,
                $row->concorrente,
                number_format($row->preco, 2, ',', '.'),
                $row->link ?? 'Link não disponível',
                Carbon::parse($row->data_extracao)->format('d/m/Y H:i')
            ], ';');
        }

        rewind($csvHandle);
        $csvContent = stream_get_contents($csvHandle);
        fclose($csvHandle);

        // 5. Envia o E-mail
        try {
            Mail::to($destinatarios)->send(new RelatorioConcorrentesMail($csvContent, Carbon::parse($hoje)->format('d/m/Y')));

            $this->info(" -> E-mail enviado com sucesso para: " . implode(', ', $destinatarios));
        } catch (\Exception $e) {
            $this->error(" -> Erro ao enviar e-mail: " . $e->getMessage());
        }
    }
}
