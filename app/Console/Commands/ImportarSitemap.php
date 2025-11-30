<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Vendedor;
use App\Models\GlobalLink;
use App\Models\LinkExterno;
use App\Models\Organizacao;
use Exception;

class ImportarSitemap extends Command
{
    protected $signature = 'sitemap:importar {organizacao_id?}';
    protected $description = 'Importa produtos de sitemaps dos concorrentes e popula as tabelas GlobalLink e LinkExterno.';

    // User Agent para evitar bloqueios simples
    const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36";

    public function handle()
    {
        $orgId = $this->argument('organizacao_id');
        $inicio = microtime(true);

        // Log de Início no Banco
        $logId = $this->registrarLogInicio($orgId ?? 0);
        $this->info("--- Iniciando Importação de Sitemap [LogID: $logId] ---");

        try {
            // Se passou ID no comando, busca só aquela organização. Se não, busca todas as ativas.
            $query = Organizacao::where('ativa', 1);
            if ($orgId) {
                $query->where('id_organizacao', $orgId);
            }
            $organizacoes = $query->get();

            $statsGeral = ['inseridos' => 0, 'existentes' => 0];

            foreach ($organizacoes as $org) {
                $this->info("Processando Organização: {$org->nome_empresa}");

                // Busca Vendedores da Organização com LinkConcorrente preenchido
                $vendedores = Vendedor::where('id_organizacao', $org->id_organizacao)
                    ->where('ativo', 1)
                    ->whereNotNull('LinkConcorrente')
                    ->where('LinkConcorrente', '<>', '')
                    ->get();

                foreach ($vendedores as $vendedor) {
                    $this->info("  -> Vendedor: {$vendedor->NomeVendedor}");

                    // Executa a lógica recursiva (Sitemap Index ou URL normal)
                    $statsVendedor = $this->processarSitemapRecursivo(
                        $vendedor->LinkConcorrente,
                        $vendedor,
                        $org->id_organizacao
                    );

                    $statsGeral['inseridos'] += $statsVendedor['inseridos'];
                    $statsGeral['existentes'] += $statsVendedor['existentes'];
                }
            }

            $duracao = microtime(true) - $inicio;
            $msg = "Sucesso. Inseridos: {$statsGeral['inseridos']}, Já existentes: {$statsGeral['existentes']}.";

            $this->registrarLogFim($logId, 'Sucesso', $duracao, $msg);
            $this->info($msg);
        } catch (Exception $e) {
            $duracao = microtime(true) - $inicio;
            $this->error("Erro Fatal: " . $e->getMessage());
            // Log de Erro no Banco
            $this->registrarLogFim($logId, 'Erro', $duracao, $e->getMessage());
        }
    }

    /**
     * Processa o Sitemap recursivamente (suporta Sitemap Index e UrlSet).
     */
    private function processarSitemapRecursivo($url, $vendedor, $orgId)
    {
        $stats = ['inseridos' => 0, 'existentes' => 0];
        $visitados = [];
        $fila = [$url];

        while (!empty($fila)) {
            $urlAtual = array_shift($fila);

            if (in_array($urlAtual, $visitados)) continue;
            $visitados[] = $urlAtual;

            try {
                // Delay de 0.5s para não sobrecarregar o site alvo
                usleep(500000);

                $response = Http::withUserAgent(self::USER_AGENT)->timeout(30)->get($urlAtual);

                if ($response->failed()) {
                    $this->warn("     [HTTP Falha] $urlAtual");
                    continue;
                }

                // Tenta carregar o XML ignorando erros de namespace
                $xml = @simplexml_load_string($response->body());
                if ($xml === false) {
                    $this->warn("     [XML Inválido] $urlAtual");
                    continue;
                }

                // CASO 1: Sitemap Index (uma lista de outros sitemaps)
                if (isset($xml->sitemap)) {
                    $this->info("     [Index] Sub-sitemaps encontrados em $urlAtual");
                    foreach ($xml->sitemap as $sitemap) {
                        $fila[] = (string)$sitemap->loc;
                    }
                    continue;
                }

                // CASO 2: UrlSet (Lista de produtos)
                $filtro = $vendedor->FiltroLinkProduto; // Ex: '/p'
                $countUrls = 0;

                foreach ($xml->url as $urlNode) {
                    $link = trim((string)$urlNode->loc);

                    // Aplica Filtro (se o usuário cadastrou um filtro no banco)
                    if (empty($filtro) || str_contains($link, $filtro)) {

                        $nomeProduto = $this->limparLinkParaNome($link, $filtro);

                        if ($nomeProduto) {
                            $resultado = $this->salvarLink($orgId, $vendedor->ID_Vendedor, $link, $nomeProduto);
                            if ($resultado === 'novo') $stats['inseridos']++;
                            else $stats['existentes']++;
                            $countUrls++;
                        }
                    }
                }
                $this->line("     -> Processados: $countUrls links válidos em $urlAtual.");
            } catch (Exception $e) {
                $this->error("     [Erro] $urlAtual: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Salva nas tabelas GlobalLink e LinkExterno.
     */
    private function salvarLink($orgId, $vendedorId, $link, $nome)
    {
        // 1. Cria ou recupera o Link Global (Único na internet)
        $globalLink = GlobalLink::firstOrCreate(
            ['link' => $link],
            [
                'ID_Vendedor' => $vendedorId,
                'status_link' => 'novo',
                'data_ultima_verificacao' => now()
            ]
        );

        // 2. Vincula à Organização (LinkExterno) se ainda não existir
        $linkExterno = LinkExterno::where('global_link_id', $globalLink->id)
            ->where('id_organizacao', $orgId)
            ->first();

        if (!$linkExterno) {
            LinkExterno::create([
                'id_organizacao' => $orgId,
                'global_link_id' => $globalLink->id,
                'nome' => $nome,
                'ativo' => 1
            ]);
            return 'novo';
        }

        return 'existente';
    }

    /**
     * Tradução fiel da função Python 'limpar_link_para_nome'
     */
    private function limparLinkParaNome($link, $filtro = null)
    {
        try {
            $linkDecodificado = urldecode($link);
            $path = parse_url($linkDecodificado, PHP_URL_PATH);

            // Remove extensões comuns
            $path = preg_replace('/\.(html|htm|php|aspx|asp)$/i', '', $path);

            $partes = array_filter(explode('/', $path));
            if (empty($partes)) return null;

            $nomeBruto = end($partes);
            $filtroLimpo = $filtro ? trim($filtro, '/') : null;

            // Se o final da URL é igual ao filtro (ex: /p), pega a parte anterior
            if ($filtroLimpo && strtolower($nomeBruto) === strtolower($filtroLimpo)) {
                if (count($partes) > 1) {
                    $nomeBruto = prev($partes);
                } else {
                    return null;
                }
            }

            // Limpeza de caracteres (hifens viram espaço)
            $nomeLimpo = preg_replace('/[-_]/', ' ', $nomeBruto);
            $nomeLimpo = preg_replace('/[^a-zA-Z0-9\s]/', '', $nomeLimpo);
            $nomeLimpo = trim(preg_replace('/\s+/', ' ', $nomeLimpo));

            return Str::ucfirst($nomeLimpo);
        } catch (Exception $e) {
            return null;
        }
    }

    // --- Logs no Banco (Tabela LogOperacoes) ---
    private function registrarLogInicio($orgId)
    {
        return DB::table('LogOperacoes')->insertGetId([
            'id_organizacao' => $orgId,
            'NomeScript' => 'ImportarSitemap (Laravel)',
            'DataHoraInicio' => now(),
            'Status' => 'Iniciado'
        ]);
    }

    private function registrarLogFim($logId, $status, $duracao, $msg)
    {
        if ($logId) {
            // Corta a mensagem caso seja muito grande para o banco
            $msgLimitada = substr($msg, 0, 65000);

            DB::table('LogOperacoes')
                ->where('LogID', $logId)
                ->update([
                    'DataHoraFim' => now(),
                    'DuracaoSegundos' => $duracao,
                    'Status' => $status,
                    'MensagemErro' => $msgLimitada
                ]);
        }
    }
}
