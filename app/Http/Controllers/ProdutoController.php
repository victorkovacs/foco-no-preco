<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $query = Produto::where('id_organizacao', $id_organizacao)->where('ativo', 1);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('SKU', 'like', "%{$search}%")
                    ->orWhere('Nome', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter_marca')) {
            $query->where('marca', $request->filter_marca);
        }

        $sortColumn = $request->get('sort', 'Nome');
        $sortDirection = $request->get('dir', 'asc');
        $allowedColumns = ['SKU', 'Nome', 'marca', 'LinkMeuSite'];

        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $produtos = $query->paginate(10)->withQueryString();
        $marcas = Produto::where('id_organizacao', $id_organizacao)->where('ativo', 1)->whereNotNull('marca')->distinct()->orderBy('marca')->pluck('marca');

        return view('produtos.index', compact('produtos', 'marcas'));
    }

    public function gerenciar(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $query = Produto::where('id_organizacao', $id_organizacao);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('SKU', 'like', "%{$search}%")
                    ->orWhere('Nome', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter_marca')) {
            $query->where('marca', $request->filter_marca);
        }

        $sortColumn = $request->get('sort', 'Nome');
        $sortDirection = $request->get('dir', 'asc');
        $allowedColumns = ['SKU', 'Nome', 'marca', 'Categoria', 'ativo'];

        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $produtos = $query->paginate(10)->withQueryString();
        $marcas = Produto::where('id_organizacao', $id_organizacao)->whereNotNull('marca')->distinct()->orderBy('marca')->pluck('marca');

        return view('produtos.gerenciar', compact('produtos', 'marcas'));
    }

    public function getDadosGrafico(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $sku = $request->sku;
        $data_inicio = $request->data_inicio;
        $data_fim = $request->data_fim;

        // CORREÇÃO PARA NOVA ESTRUTURA (GLOBAL LINKS)
        $dados = DB::table('concorrentes as c')
            ->leftJoin('Vendedores as v', 'c.ID_Vendedor', '=', 'v.ID_Vendedor')
            // Precisamos passar pelo link_externo para chegar no global, se quisermos o link atual
            // Nota: A tabela 'concorrentes' tem snapshot do preço, mas para exibir o link atual clicável:
            ->leftJoin('links_externos as l', 'c.id_link_externo', '=', 'l.id')
            ->leftJoin('global_links as gl', 'l.global_link_id', '=', 'gl.id') // <-- JOIN NOVO

            ->where('c.id_organizacao', $id_organizacao)
            ->where('c.sku', $sku)
            ->whereBetween('c.data_extracao', [$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59'])

            // Agora pegamos o link da tabela GLOBAL (gl.link) e não mais de (l.link)
            ->select('v.NomeVendedor as vendedor', 'c.preco', 'c.data_extracao', 'gl.link as link_concorrente')
            ->orderBy('c.data_extracao', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $dados]);
    }

    public function iniciarMonitoramento(Request $request)
    {
        $request->validate(['sku' => 'required|string', 'link' => 'nullable|url']);
        $id_organizacao = Auth::user()->id_organizacao;
        $produto = Produto::where('id_organizacao', $id_organizacao)->where('SKU', $request->sku)->first();

        if (!$produto) return response()->json(['success' => false, 'message' => 'Produto não encontrado.'], 404);

        $produto->EncontrouConcorrentes = 1;
        $produto->ativo = 1;
        if ($request->filled('link')) $produto->LinkPesquisa = $request->link;
        $produto->save();

        return response()->json(['success' => true, 'message' => 'Monitoramento iniciado com sucesso!']);
    }

    public function edit($id)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $produto = Produto::where('id_organizacao', $id_organizacao)->where('ID', $id)->firstOrFail();

        // CORREÇÃO PARA NOVA ESTRUTURA (GLOBAL LINKS)
        $alvos = DB::table('AlvosMonitoramento as a')
            ->leftJoin('links_externos as l', 'a.id_link_externo', '=', 'l.id')
            // O link e o vendedor agora vivem na tabela global_links
            ->leftJoin('global_links as gl', 'l.global_link_id', '=', 'gl.id') // <-- JOIN NOVO
            ->leftJoin('Vendedores as v', 'gl.ID_Vendedor', '=', 'v.ID_Vendedor') // <-- Ajuste do JOIN Vendedor

            ->where('a.ID_Produto', $id)
            // Pegamos 'gl.link' em vez de 'l.link'
            ->select('a.id_alvo as ID_Alvo', 'v.NomeVendedor', 'gl.link', 'a.id_link_externo')
            ->get();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'produto' => $produto, 'alvos' => $alvos]);
        }

        return view('produtos.editar', compact('produto', 'alvos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Nome' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'Categoria' => 'nullable|string|max:255',
            'LinkMeuSite' => 'nullable|url',
            'ativo' => 'required|in:0,1',
        ]);

        $id_organizacao = Auth::user()->id_organizacao;
        $produto = Produto::where('id_organizacao', $id_organizacao)->where('ID', $id)->firstOrFail();

        $produto->Nome = $request->Nome;
        $produto->marca = $request->marca;
        $produto->Categoria = $request->Categoria;
        $produto->LinkMeuSite = $request->LinkMeuSite;
        $produto->ativo = (int)$request->ativo;
        $produto->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso!'
            ]);
        }

        return redirect()->route('produtos.gerenciar')->with('success', 'Produto atualizado com sucesso!');
    }

    public function updateAlvoLink(Request $request, $idAlvo)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $request->validate(['novo_link' => 'required|url']);

        // 1. Busca o link externo associado ao alvo
        $alvo = DB::table('AlvosMonitoramento as a')
            ->join('links_externos as l', 'a.id_link_externo', '=', 'l.id')
            ->where('a.id_alvo', $idAlvo)
            ->where('a.id_organizacao', $id_organizacao)
            ->select('l.id as id_link_externo', 'l.global_link_id')
            ->first();

        if (!$alvo) {
            return response()->json(['success' => false, 'message' => 'Alvo não encontrado.'], 404);
        }

        // 2. Atualiza a tabela GLOBAL
        // OBS: Na nova arquitetura, atualizamos o registro global.
        // Isso corrige o link para todas as organizações que monitoram esse mesmo registro.
        DB::table('global_links')
            ->where('id', $alvo->global_link_id)
            ->update([
                'link' => $request->novo_link,
                'data_ultima_verificacao' => null, // Força rechecagem
                'status_link' => 'PENDENTE'
            ]);

        return response()->json(['success' => true, 'message' => 'Link global atualizado com sucesso.']);
    }

    public function destroy($id)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $produto = Produto::where('id_organizacao', $id_organizacao)->where('ID', $id)->firstOrFail();
        $produto->delete();
        return redirect()->route('produtos.gerenciar')->with('success', 'Produto excluído com sucesso!');
    }

    public function massUpdateForm()
    {
        return view('produtos.atualizar_em_massa');
    }

    public function massUpdateProcess(Request $request)
    {
        $request->validate(['skus' => 'required|string', 'novo_status' => 'required|boolean']);
        $id_organizacao = Auth::user()->id_organizacao;
        $skus = array_map('trim', preg_split('/[\s,]+/', $request->skus, -1, PREG_SPLIT_NO_EMPTY));

        if (empty($skus)) return back()->with('error', 'Nenhum SKU válido informado.');

        $affected = Produto::where('id_organizacao', $id_organizacao)->whereIn('SKU', $skus)->update(['ativo' => $request->novo_status]);

        if ($affected == 0) return back()->with('error', 'Nenhum produto encontrado.');
        return back()->with('success', "$affected produtos atualizados.");
    }
}
