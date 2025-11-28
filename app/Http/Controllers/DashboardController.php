<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $cache_file = "cache/stats_org_{$id_organizacao}.json";

        // Dados Padrão
        $dados = [
            'connection_error' => null,
            'competidores_ativos_data' => [],
            'count_com_concorrentes' => 0,
            'total_skus_monitorados_preco' => 0,
            'total_produtos_ativos' => 0,
            'total_pesquisados_hoje' => 0, // [NOVO] Variável solicitada
            'chart_competidores_data_json' => '[]',
            'total_com_concorrentes_json' => '0',
            'termometro_concorrentes_data_json' => json_encode(['com' => 0, 'sem' => 0, 'total' => 0]),
            'termometro_preco_data_json' => json_encode(['melhor' => 0, 'media' => 0, 'acima' => 0, 'total_monitorado' => 0]),
            'status_pesquisa_hoje_json' => json_encode(['pesquisados_hoje' => 0, 'sem_pesquisa_hoje' => 0, 'total' => 0]),
        ];

        if (!Storage::exists($cache_file)) {
            $dados['connection_error'] = "Os dados do dashboard estão sendo gerados. Atualize em alguns instantes.";
        } else {
            try {
                $json_data = Storage::get($cache_file);
                $data = json_decode($json_data, true);

                if (!is_array($data)) throw new \Exception("Arquivo de cache inválido.");

                // Variáveis JSON para os gráficos
                $dados['termometro_preco_data_json'] = json_encode($data['dados_preco'] ?? ['melhor' => 0, 'media' => 0, 'acima' => 0, 'total_monitorado' => 0]);
                $dados['total_skus_monitorados_preco'] = $data['dados_preco']['total_monitorado'] ?? 0;

                $dados['termometro_concorrentes_data_json'] = json_encode($data['status_concorrencia'] ?? ['com' => 0, 'sem' => 0, 'total' => 0]);
                $dados['status_pesquisa_hoje_json'] = json_encode($data['status_pesquisa'] ?? ['pesquisados_hoje' => 0, 'sem_pesquisa_hoje' => 0, 'total' => 0]);

                $dados['competidores_ativos_data'] = $data['dados_competidores'] ?? [];
                $dados['count_com_concorrentes'] = $data['total_com_concorrentes'] ?? 0;

                $dados['chart_competidores_data_json'] = json_encode($dados['competidores_ativos_data']);
                $dados['total_com_concorrentes_json'] = json_encode($dados['count_com_concorrentes']);

                // Totais Individuais
                $dados['total_produtos_ativos'] = $data['status_concorrencia']['total'] ?? 0;

                // [CORREÇÃO] Pega o valor exato do card "Atualizados Hoje"
                $dados['total_pesquisados_hoje'] = $data['status_pesquisa']['pesquisados_hoje'] ?? 0;
            } catch (\Exception $e) {
                $dados['connection_error'] = "Não foi possível carregar os dados atualizados.";
            }
        }

        return view('dashboard', $dados);
    }
}
