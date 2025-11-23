<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendedorController extends Controller
{
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // 1. Query Base
        $query = Vendedor::where('id_organizacao', $id_organizacao);

        // 2. Filtro de Pesquisa (Nome)
        if ($request->filled('search')) {
            $query->where('NomeVendedor', 'like', '%' . $request->search . '%');
        }

        // 3. Filtro de Status (ativos/inativos)
        // O teu script usa 'todos', 'ativos', 'inativos'
        if ($request->filled('filter_status')) {
            if ($request->filter_status === 'ativos') {
                $query->where('Ativo', 1);
            } elseif ($request->filter_status === 'inativos') {
                $query->where('Ativo', 0);
            }
        }

        // 4. Ordenação
        // Padrão do teu script: 'Ativo' DESC
        $sortColumn = $request->get('sort', 'Ativo');
        $sortDirection = $request->get('dir', 'DESC');

        $allowedColumns = ['ID_Vendedor', 'NomeVendedor', 'SeletorPreco', 'Ativo', 'PercentualDescontoAVista', 'SeletorMarca', 'FiltroLinkProduto'];

        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // 5. Paginação (15 por página, conforme o original)
        $vendedores = $query->paginate(15)->withQueryString();

        return view('vendedores.index', compact('vendedores'));
    }
}
