<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdutoController extends Controller
{
    /**
     * 1. TELA MEUS PRODUTOS (Foco em Monitoramento)
     * Lista produtos ativos com termômetros e opção de monitorar.
     */
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // Filtra apenas produtos ativos para esta tela
        $query = Produto::where('id_organizacao', $id_organizacao)
            ->where('ativo', 1);

        // Filtros de Busca (SKU, Nome, Marca)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('SKU', 'like', "%{$search}%")
                    ->orWhere('Nome', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%");
            });
        }

        // Filtro de Marca
        if ($request->filled('filter_marca')) {
            $query->where('marca', $request->filter_marca);
        }

        // Ordenação
        $sortColumn = $request->get('sort', 'Nome');
        $sortDirection = $request->get('dir', 'asc');
        $allowedColumns = ['SKU', 'Nome', 'marca', 'LinkMeuSite'];

        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // Paginação
        $produtos = $query->paginate(10)->withQueryString();

        // Lista de marcas para o Dropdown
        $marcas = Produto::where('id_organizacao', $id_organizacao)
            ->where('ativo', 1)
            ->whereNotNull('marca')
            ->distinct()
            ->orderBy('marca')
            ->pluck('marca');

        return view('produtos.index', compact('produtos', 'marcas'));
    }

    /**
     * 2. TELA DE GESTÃO (Edição/Cadastro)
     * Lista todos os produtos (ativos e inativos) para administração.
     */
    public function gerenciar(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // Aqui trazemos todos (ativos e inativos)
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

        $marcas = Produto::where('id_organizacao', $id_organizacao)
            ->whereNotNull('marca')
            ->distinct()
            ->orderBy('marca')
            ->pluck('marca');

        return view('produtos.gerenciar', compact('produtos', 'marcas'));
    }

    /**
     * 3. DADOS DO GRÁFICO (JSON para o Modal)
     * Substitui a antiga 'api_concorrentes.php'
     */
    public function getDadosGrafico(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $sku = $request->sku;
        $data_inicio = $request->data_inicio;
        $data_fim = $request->data_fim;

        // Busca histórico na tabela 'concorrentes' fazendo JOIN com 'Vendedores'
        $dados = DB::table('concorrentes as c')
            ->join('Vendedores as v', 'c.ID_Vendedor', '=', 'v.ID_Vendedor')
            ->where('c.id_organizacao', $id_organizacao)
            ->where('c.sku', $sku)
            ->whereBetween('c.data_extracao', [$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59'])
            ->select(
                'v.NomeVendedor as vendedor',
                'c.preco',
                'c.data_extracao',
                'v.LinkConcorrente as link'
            )
            ->orderBy('c.data_extracao', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dados
        ]);
    }

    /**
     * 4. INICIAR MONITORAMENTO (Ação do Botão)
     * [CORREÇÃO APLICADA]: Define EncontrouConcorrentes=1 e Ativo=1.
     */
    public function iniciarMonitoramento(Request $request)
    {
        // Validação
        $request->validate([
            'sku' => 'required|string',
            'link' => 'nullable|url'
        ]);

        $id_organizacao = Auth::user()->id_organizacao;

        // Busca o produto
        $produto = Produto::where('id_organizacao', $id_organizacao)
            ->where('SKU', $request->sku)
            ->first();

        if (!$produto) {
            return response()->json(['success' => false, 'message' => 'Produto não encontrado.'], 404);
        }

        // --- LÓGICA FIEL AO ORIGINAL ---
        $produto->EncontrouConcorrentes = 1; // Ativa flag de concorrência
        $produto->ativo = 1; // Força o produto a ficar ativo (essencial para o robô)

        // Se o usuário informou um link, atualiza
        if ($request->filled('link')) {
            $produto->LinkPesquisa = $request->link;
        }

        $produto->save();

        return response()->json([
            'success' => true,
            'message' => 'Monitoramento iniciado com sucesso!'
        ]);
    }

    /**
     * 5. EXCLUIR PRODUTO
     */
    public function destroy($id)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        $produto = Produto::where('id_organizacao', $id_organizacao)
            ->where('ID', $id)
            ->firstOrFail();

        $produto->delete();

        return redirect()->route('produtos.gerenciar')
            ->with('success', 'Produto excluído com sucesso!');
    }

    // --- ATUALIZAÇÃO EM MASSA (Formulário) ---
    public function massUpdateForm()
    {
        return view('produtos.atualizar_em_massa');
    }

    // --- ATUALIZAÇÃO EM MASSA (Processamento) ---
    public function massUpdateProcess(Request $request)
    {
        $request->validate([
            'skus' => 'required|string',
            'novo_status' => 'required|boolean'
        ]);

        $id_organizacao = Auth::user()->id_organizacao;
        $novo_status = $request->novo_status;

        // 1. Processar a lista de SKUs (separar por quebra de linha ou vírgula)
        $raw_skus = $request->skus;
        $skus = preg_split('/[\s,]+/', $raw_skus, -1, PREG_SPLIT_NO_EMPTY);
        $skus = array_map('trim', $skus); // Remove espaços extras

        if (empty($skus)) {
            return back()->with('error', 'Nenhum SKU válido foi informado.');
        }

        // 2. Atualizar no Banco
        $affected = Produto::where('id_organizacao', $id_organizacao)
            ->whereIn('SKU', $skus)
            ->update(['ativo' => $novo_status]);

        // 3. Retornar com mensagem
        $msg = $affected . ' produtos foram atualizados para ' . ($novo_status ? 'ATIVO' : 'INATIVO') . '.';

        if ($affected == 0) {
            return back()->with('error', 'Nenhum produto encontrado com os SKUs informados.');
        }

        return back()->with('success', $msg);
    }
}
