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

        // CORREÇÃO: Carregamos o caminho completo até o vendedor
        // 'linkExterno.globalLink.vendedor' garante que os dados estejam prontos para o Accessor
        $query = AlvoMonitoramento::with(['produto', 'linkExterno.globalLink.vendedor'])
            ->where('id_organizacao', $id_organizacao);

        // Filtro de Data
        if ($request->filled('date')) {
            $query->whereDate('data_ultima_verificacao', $request->date);
        }

        // Filtro de Vendedor (CORRIGIDO PARA NOVA ESTRUTURA)
        if ($request->filled('vendedor') && $request->vendedor !== 'todos') {
            $idVendedor = $request->vendedor;

            // Navega: Alvo -> LinkExterno -> GlobalLink -> Vendedor (ID)
            $query->whereHas('linkExterno.globalLink', function ($q) use ($idVendedor) {
                $q->where('ID_Vendedor', $idVendedor);
            });
        }

        // Filtro de Status
        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status_verificacao', $request->status);
        }

        $resultados = $query->orderBy('data_ultima_verificacao', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $resultados->items(),
            'current_page' => $resultados->currentPage(),
            'last_page' => $resultados->lastPage(),
            'total' => $resultados->total(),
        ]);
    }
}
