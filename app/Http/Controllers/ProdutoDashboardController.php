<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdutoDashboardController extends Controller
{
    // ... (Mantenha os métodos index, store e import como estavam) ...

    public function index(Request $request)
    {
        // (Código do index igual ao anterior...)
        $id_organizacao = Auth::user()->id_organizacao;

        $query = DB::table('FilaGeracaoConteudo')
            ->leftJoin('templates_ia', 'FilaGeracaoConteudo.id_template_ia', '=', 'templates_ia.id')
            ->where('FilaGeracaoConteudo.id_organizacao', $id_organizacao)
            ->select(
                'FilaGeracaoConteudo.id as id_fila',
                'FilaGeracaoConteudo.status',
                'FilaGeracaoConteudo.mensagem_erro',
                'FilaGeracaoConteudo.created_at as data_entrada',
                'FilaGeracaoConteudo.updated_at as data_atualizacao',
                'FilaGeracaoConteudo.sku',
                'FilaGeracaoConteudo.nome_produto',
                'FilaGeracaoConteudo.palavra_chave_entrada',
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

        $stats = [
            'total_fila'  => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->count(),
            'pendente'    => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->where('status', 'pendente')->count(),
            'processando' => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->where('status', 'processando')->count(),
            'concluido'   => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->where('status', 'concluido')->count(),
            'falhou'      => DB::table('FilaGeracaoConteudo')->where('id_organizacao', $id_organizacao)->where('status', 'erro')->count(),
        ];

        $itensFila = $query->orderBy('FilaGeracaoConteudo.id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $templates = TemplateIa::where('id_organizacao', $id_organizacao)
            ->where('ativo', 1)
            ->orderBy('nome_template')
            ->get();

        return view('produtos_dashboard.index', compact('itensFila', 'stats', 'templates'));
    }

    public function store(Request $request)
    {
        // (Código do store igual ao anterior...)
        $request->validate([
            'sku' => 'required|string',
            'palavra_chave' => 'required|string',
            'id_template_manual' => 'required|exists:templates_ia,id'
        ]);

        $id_organizacao = Auth::user()->id_organizacao;

        $produto = Produto::where('id_organizacao', $id_organizacao)
            ->where('SKU', $request->sku)
            ->first();

        if (!$produto) {
            return response()->json(['success' => false, 'message' => 'SKU não encontrado na base de produtos.']);
        }

        DB::table('FilaGeracaoConteudo')->insert([
            'id_produto' => $produto->ID,
            'id_organizacao' => $id_organizacao,
            'id_template_ia' => $request->id_template_manual,
            'sku' => $produto->SKU,
            'nome_produto' => $produto->Nome,
            'palavra_chave_entrada' => $request->palavra_chave,
            'status' => 'pendente',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Produto adicionado à fila com sucesso!']);
    }

    public function import(Request $request)
    {
        // (Código do import igual ao anterior...)
        $request->validate(['arquivo_csv' => 'required|file|mimes:csv,txt']);

        $id_organizacao = Auth::user()->id_organizacao;
        $file = $request->file('arquivo_csv');

        if (($handle = fopen($file->getPathname(), 'r')) !== FALSE) {
            $row = 0;
            $inseridos = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                if (count($data) < 3) continue;

                $sku = trim($data[0]);
                if ($row == 1 && strtolower($sku) == 'sku') continue;

                $produto = Produto::where('id_organizacao', $id_organizacao)->where('SKU', $sku)->first();

                if ($produto) {
                    DB::table('FilaGeracaoConteudo')->insert([
                        'id_produto' => $produto->ID,
                        'id_organizacao' => $id_organizacao,
                        'id_template_ia' => trim($data[2]),
                        'sku' => $produto->SKU,
                        'nome_produto' => $produto->Nome,
                        'palavra_chave_entrada' => trim($data[1]),
                        'status' => 'pendente',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $inseridos++;
                }
            }
            fclose($handle);

            return response()->json(['success' => true, 'message' => "$inseridos itens importados com sucesso."]);
        }

        return response()->json(['success' => false, 'message' => 'Erro ao ler arquivo.']);
    }

    // --- NOVO MÉTODO: Gerar CSV Dinamicamente CORRIGIDO ---
    public function downloadTemplate()
    {
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8", // Garantindo UTF-8 para acentuação
            "Content-Disposition" => "attachment; filename=modelo_importacao.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['SKU', 'PALAVRA_CHAVE', 'ID_TEMPLATE'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');

            // Força o charset para UTF-8 (útil para acentuação)
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CORREÇÃO 1: Adiciona a linha de separação para Excel (força o delimitador ;)
            fputs($file, "sep=;\n");

            // CORREÇÃO 2: Usa fputcsv com o delimitador explícito (;)
            fputcsv($file, $columns, ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function sendBatch(Request $request)
    {
        return response()->json(['success' => false, 'message' => 'Use a importação manual ou CSV.']);
    }
}
