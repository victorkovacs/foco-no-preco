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

        // 1. Filtro de Dias (Padrão: 30)
        $dias = $request->get('filtro', 30);
        if (!in_array($dias, ['7', '30', '60'])) {
            $dias = 30;
        }

        // 2. Query Agrupada por Dia (Para o Gráfico)
        $dadosDiarios = LogIaToken::where('id_organizacao', $id_organizacao)
            ->where('data_registro', '>=', now()->subDays($dias))
            ->select(
                DB::raw('DATE(data_registro) as data'),
                DB::raw('SUM(tokens_in) as total_in'),
                DB::raw('SUM(tokens_out) as total_out')
            )
            ->groupBy('data')
            ->orderBy('data', 'asc')
            ->get();

        // 3. Calcular Totais e Médias (Cards)
        $totalIn = $dadosDiarios->sum('total_in');
        $totalOut = $dadosDiarios->sum('total_out');
        $totalGeral = $totalIn + $totalOut;

        // Evita divisão por zero
        $diasComDados = $dadosDiarios->count() > 0 ? $dadosDiarios->count() : 1;
        $mediaDiaria = round($totalGeral / $diasComDados);

        // 4. Preparar dados para o Chart.js
        $graficoLabels = $dadosDiarios->pluck('data')->map(function ($date) {
            return date('d/m', strtotime($date));
        });
        $graficoDataIn = $dadosDiarios->pluck('total_in');
        $graficoDataOut = $dadosDiarios->pluck('total_out');

        return view('custos_ia.index', compact(
            'dias',
            'totalIn',
            'totalOut',
            'mediaDiaria',
            'graficoLabels',
            'graficoDataIn',
            'graficoDataOut'
        ));
    }
}
