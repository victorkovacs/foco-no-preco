<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Vendedor;
use App\Models\Produto;

class CuradoriaController extends Controller
{
    public function index()
    {
        return view('curadoria.index');
    }

    public function search(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $data_pesquisa = $request->input('data_pesquisa', date('Y-m-d'));
        $page = $request->input('p', 1);

        // --- ALTERAÇÃO SOLICITADA: 15 ITENS POR PÁGINA ---
        $limit = 15;

        $filtro_vendedor = $request->input('filtro_vendedor_id', 'todos');
        $filtro_status = $request->input('filtro_status', 'todos');

        // 1. Vendedores (Colunas)
        $queryVendedores = Vendedor::where('id_organizacao', $id_organizacao)->where('Ativo', 1);
        if ($filtro_vendedor !== 'todos') {
            $queryVendedores->where('ID_Vendedor', $filtro_vendedor);
        }
        $vendedores = $queryVendedores->orderBy('NomeVendedor')->get(['ID_Vendedor', 'NomeVendedor']);

        // 2. Produtos (Linhas)
        $queryProdutos = Produto::where('id_organizacao', $id_organizacao)->where('ativo', 1);

        if ($request->filled('search')) {
            $term = $request->search;
            $queryProdutos->where(function ($q) use ($term) {
                $q->where('SKU', 'like', "%$term%")
                    ->orWhere('Nome', 'like', "%$term%");
            });
        }

        // 3. Filtros de Status
        if ($filtro_vendedor !== 'todos' && $filtro_status !== 'todos') {
            if ($filtro_status === 'pesquisado') {
                $queryProdutos->whereHas('alvos', function ($q) use ($filtro_vendedor, $data_pesquisa) {
                    $q->whereHas('linkExterno.globalLink', function ($gl) use ($filtro_vendedor) {
                        $gl->where('ID_Vendedor', $filtro_vendedor);
                    })->whereDate('data_ultima_verificacao', $data_pesquisa);
                });
            } elseif ($filtro_status === 'nao_pesquisado') {
                $queryProdutos->whereHas('alvos', function ($q) use ($filtro_vendedor, $data_pesquisa) {
                    $q->whereHas('linkExterno.globalLink', function ($gl) use ($filtro_vendedor) {
                        $gl->where('ID_Vendedor', $filtro_vendedor);
                    })->where(function ($sub) use ($data_pesquisa) {
                        $sub->whereDate('data_ultima_verificacao', '!=', $data_pesquisa)
                            ->orWhereNull('data_ultima_verificacao');
                    });
                });
            } elseif ($filtro_status === 'sem_link') {
                $queryProdutos->whereDoesntHave('alvos', function ($q) use ($filtro_vendedor) {
                    $q->whereHas('linkExterno.globalLink', function ($gl) use ($filtro_vendedor) {
                        $gl->where('ID_Vendedor', $filtro_vendedor);
                    });
                });
            }
        }

        $produtos = $queryProdutos->orderBy('Nome', 'ASC')->paginate($limit, ['*'], 'p', $page);

        // 4. Matriz de Status
        $skusPagina = $produtos->pluck('SKU')->toArray();
        $dadosMatriz = [];

        if (!empty($skusPagina)) {
            $dadosRaw = DB::table('links_externos as le')
                ->join('global_links as gl', 'le.global_link_id', '=', 'gl.id')
                ->leftJoin('concorrentes as c', function ($join) use ($data_pesquisa) {
                    $join->on('le.id', '=', 'c.id_link_externo')
                        ->whereDate('c.data_extracao', $data_pesquisa);
                })
                ->where('le.id_organizacao', $id_organizacao)
                ->whereIn('le.SKU', $skusPagina)
                ->select('le.SKU', 'gl.ID_Vendedor', 'c.id as tem_concorrente')
                ->get();

            foreach ($dadosRaw as $d) {
                $status = $d->tem_concorrente ? 'pesquisado' : 'nao_pesquisado';
                $dadosMatriz[$d->SKU][$d->ID_Vendedor] = $status;
            }
        }

        // 5. Formatar
        $produtosFormatados = [];
        foreach ($produtos as $prod) {
            $statusPorVendedor = [];
            foreach ($vendedores as $vend) {
                $vid = $vend->ID_Vendedor;
                $status = $dadosMatriz[$prod->SKU][$vid] ?? 'sem_link';
                $statusPorVendedor[$vid] = $status;
            }
            $produtosFormatados[] = [
                'ID' => $prod->ID,
                'SKU' => $prod->SKU,
                'Nome' => $prod->Nome,
                'status' => $statusPorVendedor
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'vendedores' => $vendedores,
                'produtos' => $produtosFormatados
            ],
            'pagination' => [
                'currentPage' => $produtos->currentPage(),
                'totalPages' => $produtos->lastPage(),
                'totalRows' => $produtos->total()
            ]
        ]);
    }
}
