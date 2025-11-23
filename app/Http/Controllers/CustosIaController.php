<?php

namespace App\Http\Controllers;

use App\Models\LogIaToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustosIaController extends Controller
{
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // 1. Filtro de Dias
        $dias = $request->get('filtro', 30);
        if (!in_array($dias, ['7', '30', '60'])) {
            $dias = 30;
        }
        $dataInicio = now()->subDays($dias);

        // 2. Query para o Gráfico (Agrupado por Dia)
        $dadosDiarios = LogIaToken::where('id_organizacao', $id_organizacao)
            ->where('data_registro', '>=', $dataInicio)
            ->select(
                DB::raw('DATE(data_registro) as data'),
                DB::raw('SUM(tokens_in) as total_in'),
                DB::raw('SUM(tokens_out) as total_out')
            )
            ->groupBy('data')
            ->orderBy('data', 'asc')
            ->get();

        // 3. Calcular Totais de Tokens
        $totalIn = $dadosDiarios->sum('total_in');
        $totalOut = $dadosDiarios->sum('total_out');
        $mediaDiaria = round(($totalIn + $totalOut) / max(1, $dadosDiarios->count()));

        // 4. CÁLCULO FINANCEIRO PRECISO (Agrupado por Modelo)
        // Buscamos o consumo separado por modelo para aplicar o preço correto
        $consumoPorModelo = LogIaToken::where('id_organizacao', $id_organizacao)
            ->where('data_registro', '>=', $dataInicio)
            ->select(
                'modelo',
                DB::raw('SUM(tokens_in) as total_in'),
                DB::raw('SUM(tokens_out) as total_out')
            )
            ->groupBy('modelo')
            ->get();

        // Tabela de Preços (USD por 1 Milhão de Tokens)
        // Fonte: OpenAI Pricing (Atualize conforme necessário)
        $precos = [
            'gpt-4o' => ['in' => 2.50, 'out' => 10.00],
            'gpt-4o-mini' => ['in' => 0.15, 'out' => 0.60],
            // Fallback para outros modelos
            'default' => ['in' => 0.15, 'out' => 0.60],
        ];

        $cotacaoDolar = 6.10; // Pode ajustar ou pegar de uma API
        $custoTotalUSD = 0;

        foreach ($consumoPorModelo as $item) {
            // Identifica o preço do modelo (ou usa o default)
            $p = $precos[$item->modelo] ?? $precos['default'];

            $custoIn = ($item->total_in / 1000000) * $p['in'];
            $custoOut = ($item->total_out / 1000000) * $p['out'];

            $custoTotalUSD += ($custoIn + $custoOut);
        }

        $custoTotalBRL = $custoTotalUSD * $cotacaoDolar;

        // 5. Dados para o Gráfico
        $graficoLabels = $dadosDiarios->pluck('data')->map(fn($d) => date('d/m', strtotime($d)));
        $graficoDataIn = $dadosDiarios->pluck('total_in');
        $graficoDataOut = $dadosDiarios->pluck('total_out');

        return view('custos_ia.index', compact(
            'dias',
            'totalIn',
            'totalOut',
            'mediaDiaria',
            'custoTotalBRL',
            'graficoLabels',
            'graficoDataIn',
            'graficoDataOut'
        ));
    }
}
