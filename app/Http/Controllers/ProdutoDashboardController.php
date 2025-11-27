<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdutoDashboardController extends Controller
{
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;

        // ✅ CORREÇÃO: Filtrar a fila pela organização do usuário
        $query = DB::table('FilaGeracaoConteudo')
            ->leftJoin('templates_ia', 'FilaGeracaoConteudo.id_template_ia', '=', 'templates_ia.id')
            ->where('FilaGeracaoConteudo.id_organizacao', $id_organizacao) // <--- TRAVA DE SEGURANÇA
            ->select(
                'FilaGeracaoConteudo.id as id_fila',
                'FilaGeracaoConteudo.status',
                'FilaGeracaoConteudo.mensagem_erro',
                'FilaGeracaoConteudo.created_at as data_entrada',
                'FilaGeracaoConteudo.updated_at as data_atualizacao',
                'FilaGeracaoConteudo.sku',
                'FilaGeracaoConteudo.nome_produto',
                'templates_ia.nome_template'
            );

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

        // Estatísticas também precisam ser filtradas
        $stats = [
            'total_fila'  => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->count(),
            'pendente'    => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->where('status', 'pendente')->count(),
            'processando' => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->where('status', 'processando')->count(),
            'concluido'   => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->where('status', 'concluido')->count(),
        ];

        $itensFila = $query->orderBy('FilaGeracaoConteudo.id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Templates apenas da organização ou ativos globais
        $templates = TemplateIa::where('id_organizacao', $id_organizacao)
            ->where('ativo', 1)
            ->orderBy('nome_template')
            ->get();

        return view('produtos_dashboard.index', compact('itensFila', 'stats', 'templates'));
    }

    public function sendBatch(Request $request)
    {
        $request->validate([
            'skus' => 'required|string',
            'template_id' => 'required|exists:templates_ia,id'
        ]);

        $id_organizacao = Auth::user()->id_organizacao;

        $skusRaw = preg_split('/[\s,]+/', $request->skus, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($skusRaw)) {
            return response()->json(['success' => false, 'message' => 'Nenhum SKU válido informado.']);
        }

        $produtos = Produto::where('id_organizacao', $id_organizacao)
            ->whereIn('SKU', $skusRaw)
            ->select('ID', 'Nome', 'SKU')
            ->get();

        if ($produtos->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Nenhum produto encontrado.']);
        }

        $dadosInsert = [];
        $agora = now();

        foreach ($produtos as $prod) {
            $dadosInsert[] = [
                'id_produto' => $prod->ID,
                'id_organizacao' => $id_organizacao, // ✅ CORREÇÃO: Salvar quem é o dono
                'id_template_ia' => $request->template_id,
                'sku' => $prod->SKU,
                'nome_produto' => $prod->Nome,
                'palavra_chave_entrada' => $prod->Nome,
                'status' => 'pendente',
                'created_at' => $agora,
                'updated_at' => $agora
            ];
        }

        DB::table('FilaGeracaoConteudo')->insert($dadosInsert);

        return response()->json([
            'success' => true,
            'message' => count($dadosInsert) . ' tarefas enviadas para a fila.'
        ]);
    }
}
