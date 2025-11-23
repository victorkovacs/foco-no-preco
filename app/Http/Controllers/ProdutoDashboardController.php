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

        // 1. Estatísticas (Cards do Topo)
        // Mapeando ia_processado: 0 = Pendente/Espera, 1 = Concluído
        $stats = [
            'total' => Produto::where('id_organizacao', $id_organizacao)->count(),
            'pendente' => Produto::where('id_organizacao', $id_organizacao)->where('ia_processado', 0)->count(),
            'concluido' => Produto::where('id_organizacao', $id_organizacao)->where('ia_processado', 1)->count(),
            'erro' => 0 // Se tiveres um status de erro no futuro, ajusta aqui
        ];

        // 2. Query Principal
        $query = Produto::where('id_organizacao', $id_organizacao);

        // Filtro de Status (ia_processado)
        if ($request->has('status') && $request->status !== '') {
            if ($request->status == 'concluido') {
                $query->where('ia_processado', 1);
            } elseif ($request->status == 'pendente') {
                $query->where('ia_processado', 0);
            }
        }

        // Filtro de Busca
        if ($request->filled('search')) {
            $query->where('Nome', 'like', '%' . $request->search . '%');
        }

        $produtos = $query->orderBy('ID', 'desc')->paginate(15)->withQueryString();

        // 3. Lista de Templates (Para o Modal de Novo Produto)
        $templates = TemplateIa::where('ativo', 1)->orderBy('nome_template')->get();

        return view('produtos_dashboard.index', compact('produtos', 'stats', 'templates'));
    }

    // Função para adicionar produto (Simples)
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'template_id' => 'nullable|exists:templates_ia,id'
        ]);

        Produto::create([
            'id_organizacao' => Auth::user()->id_organizacao,
            'Nome' => $request->nome,
            'SKU' => $request->sku,
            'id_template_ia' => $request->template_id,
            'ativo' => 1,
            'ia_processado' => 0 // Começa pendente
        ]);

        return redirect()->route('produtos_dashboard.index')->with('success', 'Produto adicionado com sucesso!');
    }
}
