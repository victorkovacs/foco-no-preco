<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProdutoDashboardController extends Controller
{
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // 1. Estatísticas (Mantidas para visualização geral)
        $stats = [
            'total' => Produto::where('id_organizacao', $id_organizacao)->count(),
            'pendente' => Produto::where('id_organizacao', $id_organizacao)->where('ia_processado', 0)->count(),
            'concluido' => Produto::where('id_organizacao', $id_organizacao)->where('ia_processado', 1)->count(),
            'erro' => 0
        ];

        // 2. Query Principal (Logica Ajustada)
        $query = Produto::where('id_organizacao', $id_organizacao);

        // CORREÇÃO: Só carrega a lista se houver uma busca ativa ou filtro de status.
        // Caso contrário, retorna vazio para não poluir a tela de cadastro.
        $temFiltro = false;

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('Nome', 'like', '%' . $request->search . '%')
                    ->orWhere('SKU', 'like', '%' . $request->search . '%');
            });
            $temFiltro = true;
        }

        if ($request->has('status') && $request->status !== '') {
            if ($request->status == 'concluido') {
                $query->where('ia_processado', 1);
            } elseif ($request->status == 'pendente') {
                $query->where('ia_processado', 0);
            }
            $temFiltro = true;
        }

        if ($temFiltro) {
            // Se tiver filtro, busca os resultados
            $produtos = $query->orderBy('ID', 'desc')->paginate(15)->withQueryString();
        } else {
            // Se NÃO tiver filtro, retorna uma lista vazia (Paginator vazio)
            // Isso faz a tabela sumir/ficar vazia inicialmente
            $produtos = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        // 3. Lista de Templates (Para o Modal de Novo Produto)
        $templates = TemplateIa::where('ativo', 1)->orderBy('nome_template')->get();

        return view('produtos_dashboard.index', compact('produtos', 'stats', 'templates'));
    }

    // Função para adicionar produto
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'template_id' => 'nullable|exists:templates_ia,id'
        ]);

        // Cria o produto
        Produto::create([
            'id_organizacao' => Auth::user()->id_organizacao,
            'Nome' => $request->nome,
            'SKU' => $request->sku,
            'id_template_ia' => $request->template_id,
            'ativo' => 1,
            'ia_processado' => 0
        ]);

        // Redireciona filtrando pelo status 'pendente' para que o usuário veja o item que acabou de criar
        return redirect()->route('produtos_dashboard.index', ['status' => 'pendente'])
            ->with('success', 'Produto adicionado com sucesso!');
    }
}
