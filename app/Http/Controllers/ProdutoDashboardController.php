<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdutoDashboardController extends Controller
{
    /**
     * Exibe o Monitor da Fila.
     * A leitura é feita ESTRITAMENTE na tabela FilaGeracaoConteudo.
     */
    public function index(Request $request)
    {
        // 1. Query Principal (Fila + Templates)
        // Não fazemos JOIN com Produtos aqui para garantir isolamento.
        $query = DB::table('FilaGeracaoConteudo')
            ->leftJoin('templates_ia', 'FilaGeracaoConteudo.id_template_ia', '=', 'templates_ia.id')
            ->select(
                'FilaGeracaoConteudo.id as id_fila',
                'FilaGeracaoConteudo.status',
                'FilaGeracaoConteudo.mensagem_erro',
                'FilaGeracaoConteudo.created_at as data_entrada',
                'FilaGeracaoConteudo.updated_at as data_atualizacao',
                // Lendo as colunas de snapshot que criamos na migration
                'FilaGeracaoConteudo.sku',
                'FilaGeracaoConteudo.nome_produto',
                'templates_ia.nome_template'
            );

        // 2. Filtros (Busca nos dados da própria fila)
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('FilaGeracaoConteudo.nome_produto', 'like', "%{$term}%")
                    ->orWhere('FilaGeracaoConteudo.sku', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('FilaGeracaoConteudo.status', $request->status);
        }

        // 3. Estatísticas Rápidas (Counts diretos na tabela da fila)
        $stats = [
            'total_fila'  => DB::table('FilaGeracaoConteudo')->count(),
            'pendente'    => DB::table('FilaGeracaoConteudo')->where('status', 'pendente')->count(),
            'processando' => DB::table('FilaGeracaoConteudo')->where('status', 'processando')->count(),
            'concluido'   => DB::table('FilaGeracaoConteudo')->where('status', 'concluido')->count(),
        ];

        // 4. Ordenação e Paginação
        // Ordena por ID decrescente (tarefas mais recentes no topo)
        $itensFila = $query->orderBy('FilaGeracaoConteudo.id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // 5. Carrega templates (Apenas para preencher o select do Modal de Nova Tarefa)
        $templates = TemplateIa::where('ativo', 1)->orderBy('nome_template')->get();

        return view('produtos_dashboard.index', compact('itensFila', 'stats', 'templates'));
    }

    /**
     * Recebe uma lista de SKUs e cria tarefas na fila.
     * Aqui consultamos a tabela Produtos apenas para copiar os dados iniciais.
     */
    public function sendBatch(Request $request)
    {
        $request->validate([
            'skus' => 'required|string',
            'template_id' => 'required|exists:templates_ia,id'
        ]);

        $id_organizacao = Auth::user()->id_organizacao;

        // Processa o texto (quebra linhas ou vírgulas e remove espaços vazios)
        $skusRaw = preg_split('/[\s,]+/', $request->skus, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($skusRaw)) {
            return response()->json(['success' => false, 'message' => 'Nenhum SKU válido informado.']);
        }

        // Busca os produtos originais para "tirar a foto" dos dados
        $produtos = Produto::where('id_organizacao', $id_organizacao)
            ->whereIn('SKU', $skusRaw)
            ->select('ID', 'Nome', 'SKU')
            ->get();

        if ($produtos->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Nenhum produto encontrado com esses SKUs.']);
        }

        $dadosInsert = [];
        $agora = now();

        foreach ($produtos as $prod) {
            $dadosInsert[] = [
                'id_produto' => $prod->ID,      // Referência numérica (sem FK estrita)
                'id_template_ia' => $request->template_id,
                'sku' => $prod->SKU,            // SNAPSHOT: Salva o SKU atual na fila
                'nome_produto' => $prod->Nome,  // SNAPSHOT: Salva o Nome atual na fila
                'palavra_chave_entrada' => $prod->Nome, // Prompt inicial
                'status' => 'pendente',
                'created_at' => $agora,
                'updated_at' => $agora
            ];
        }

        // Inserção em massa (Bulk Insert)
        DB::table('FilaGeracaoConteudo')->insert($dadosInsert);

        return response()->json([
            'success' => true,
            'message' => count($dadosInsert) . ' tarefas adicionadas à fila com sucesso!'
        ]);
    }
}
