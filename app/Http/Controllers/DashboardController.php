<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Configuração Inicial
        $id_organizacao = Auth::user()->id_organizacao;
        
        // Definimos onde o arquivo JSON deve estar. 
        // Sugestão: Guardar em 'storage/app/cache/' para organização.
        $cache_file_path = storage_path("app/cache/stats_org_{$id_organizacao}.json");

        // 2. Dados Padrão (Vazios)
        $dados = [
            'connection_error' => null,
            'competidores_ativos_data' => [],
            'count_com_concorrentes' => 0,
            'total_skus_monitorados_preco' => 0,
            'chart_competidores_data_json' => '[]',
            'total_com_concorrentes_json' => '0',
            'termometro_concorrentes_data_json' => json_encode(['com' => 0, 'sem' => 0, 'total' => 0]),
            'termometro_preco_data_json' => json_encode(['melhor' => 0, 'media' => 0, 'acima' => 0, 'total_monitorado' => 0]),
            'status_pesquisa_hoje_json' => json_encode(['pesquisados_hoje' => 0, 'sem_pesquisa_hoje' => 0, 'total' => 0]),
        ];

        // 3. Ler o Cache
        if (!File::exists($cache_file_path)) {
            $dados['connection_error'] = "Os dados do dashboard estão sendo gerados. Por favor, atualize a página em alguns minutos.";
        } else {
            try {
                $json_data = File::get($cache_file_path);
                $data = json_decode($json_data, true);

                // Popula as variáveis
                $dados['termometro_preco_data_json'] = json_encode($data['dados_preco'] ?? ['melhor' => 0, 'media' => 0, 'acima' => 0, 'total_monitorado' => 0]);
                $dados['total_skus_monitorados_preco'] = $data['dados_preco']['total_monitorado'] ?? 0;

                $dados['termometro_concorrentes_data_json'] = json_encode($data['status_concorrencia'] ?? ['com' => 0, 'sem' => 0, 'total' => 0]);
                
                $dados['status_pesquisa_hoje_json'] = json_encode($data['status_pesquisa'] ?? ['pesquisados_hoje' => 0, 'sem_pesquisa_hoje' => 0, 'total' => 0]);

                $dados['competidores_ativos_data'] = $data['dados_competidores'] ?? [];
                $dados['count_com_concorrentes'] = $data['total_com_concorrentes'] ?? 0;

                $dados['chart_competidores_data_json'] = json_encode($dados['competidores_ativos_data']);
                $dados['total_com_concorrentes_json'] = json_encode($dados['count_com_concorrentes']);

            } catch (\Exception $e) {
                $dados['connection_error'] = "Erro ao ler o arquivo de cache: " . $e->getMessage();
            }
        }

        // Retorna a View passando os dados
        return view('dashboard', $dados);
    }
}