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

        $query = Vendedor::where('id_organizacao', $id_organizacao);

        if ($request->filled('search')) {
            $query->where('NomeVendedor', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('filter_status')) {
            if ($request->filter_status === 'ativos') {
                $query->where('Ativo', 1);
            } elseif ($request->filter_status === 'inativos') {
                $query->where('Ativo', 0);
            }
        }

        $sortColumn = $request->get('sort', 'Ativo');
        $sortDirection = $request->get('dir', 'DESC');
        $allowedColumns = ['ID_Vendedor', 'NomeVendedor', 'SeletorPreco', 'Ativo', 'PercentualDescontoAVista', 'SeletorMarca', 'FiltroLinkProduto'];

        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $vendedores = $query->paginate(15)->withQueryString();

        return view('vendedores.index', compact('vendedores'));
    }

    // NOVO MÉTODO: Atualiza o vendedor via AJAX (Modal)
    public function update(Request $request, $id)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // 1. Validação
        $request->validate([
            'SeletorPreco' => 'nullable|string|max:255',
            'PercentualDescontoAVista' => 'nullable|numeric|min:0|max:100',
            'FiltroLinkProduto' => 'nullable|string|max:255',
            'LinkConcorrente' => 'nullable|url|max:255',
            'Ativo' => 'required|in:0,1',
        ]);

        // 2. Busca o Vendedor (Garante que pertence à organização)
        $vendedor = Vendedor::where('ID_Vendedor', $id)
            ->where('id_organizacao', $id_organizacao)
            ->firstOrFail();

        // 3. Atualiza os dados
        $vendedor->SeletorPreco = $request->SeletorPreco;
        $vendedor->PercentualDescontoAVista = $request->PercentualDescontoAVista;
        $vendedor->FiltroLinkProduto = $request->FiltroLinkProduto;
        $vendedor->LinkConcorrente = $request->LinkConcorrente;
        $vendedor->Ativo = (int)$request->Ativo;

        $vendedor->save();

        // 4. Retorna JSON para o JavaScript do modal
        return response()->json([
            'success' => true,
            'message' => 'Concorrente atualizado com sucesso!'
        ]);
    }
}
