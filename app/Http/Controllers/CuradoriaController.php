<?php

namespace App\Http\Controllers;

use App\Models\AlvoMonitoramento;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CuradoriaController extends Controller
{
    // 1. Carregar a Página HTML
    public function index()
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // Carrega lista de vendedores para o filtro (Select)
        $vendedores = Vendedor::where('id_organizacao', $id_organizacao)
            ->where('Ativo', 1)
            ->orderBy('NomeVendedor')
            ->get();

        return view('curadoria.index', compact('vendedores'));
    }

    // 2. API para buscar dados via AJAX (Fetch)
    public function search(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // Inicia a query com os relacionamentos
        $query = AlvoMonitoramento::with(['produto', 'vendedor'])
            ->where('id_organizacao', $id_organizacao);

        // Filtro de Data (data_ultima_verificacao)
        if ($request->filled('date')) {
            $query->whereDate('data_ultima_verificacao', $request->date);
        }

        // Filtro de Vendedor
        if ($request->filled('vendedor') && $request->vendedor !== 'todos') {
            $query->where('id_link_externo', $request->vendedor);
        }

        // Filtro de Status (OK, ERRO, etc)
        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status_verificacao', $request->status);
        }

        // Paginação
        $resultados = $query->orderBy('data_ultima_verificacao', 'desc')
            ->paginate(20);

        // Formata os dados para JSON
        return response()->json([
            'data' => $resultados->items(),
            'current_page' => $resultados->currentPage(),
            'last_page' => $resultados->lastPage(),
            'total' => $resultados->total(),
        ]);
    }
}
