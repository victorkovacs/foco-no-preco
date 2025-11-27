<?php

namespace App\Http\Controllers;

use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateIaController extends Controller
{
    // Tela Principal
    public function index()
    {
        return view('templates_ia.index');
    }

    // API: Listar Templates
    public function list()
    {
        // Se a coluna id_organizacao ainda não existir, use esta linha comentada temporariamente:
        // $templates = TemplateIa::orderBy('nome_template')->get();

        $templates = TemplateIa::where('id_organizacao', Auth::user()->id_organizacao)
            ->orderBy('nome_template')
            ->get();

        return response()->json($templates);
    }

    // API: Salvar
    public function store(Request $request)
    {
        $request->validate([
            'nome_template' => 'required|string|max:255',
            'prompt_sistema' => 'required|string',
        ]);

        $id_organizacao = Auth::user()->id_organizacao;
        $dados = $request->only(['nome_template', 'prompt_sistema', 'json_schema_saida']);

        // Garante id_organizacao
        $dados['id_organizacao'] = $id_organizacao;
        $dados['ativo'] = true;

        // Create ou Update baseado no ID
        $template = TemplateIa::updateOrCreate(
            [
                'id' => $request->input('id'),
                'id_organizacao' => $id_organizacao // Segurança: só edita se for da org
            ],
            $dados
        );

        return response()->json(['success' => true, 'message' => 'Salvo com sucesso!']);
    }

    // API: Buscar Unico
    public function show($id)
    {
        $template = TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->firstOrFail();

        return response()->json($template);
    }

    // API: Excluir
    public function destroy($id)
    {
        TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->delete();

        return response()->json(['success' => true]);
    }

    // API: Gerar Prompt Automático (IA)
    public function gerarPromptAutomatico(Request $request)
    {
        $request->validate(['exemplo_saida' => 'required|string|min:10']);

        $apiKey = config('services.google.key') ?? env('GEMINI_API_KEY');
        if (!$apiKey) return response()->json(['sucesso' => false, 'erro' => 'API Key ausente.'], 500);

        $prompt = <<<TEXT
ATUAÇÃO: Engenheiro de Prompt Sênior.
TAREFA: Engenharia Reversa do texto abaixo. Crie um "prompt_sistema" (instruções para IA) e um "json_schema_saida" (se aplicável) para gerar textos similares.
Retorne APENAS JSON.

EXEMPLO:
{$request->exemplo_saida}
TEXT;

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json']
            ]);

            $json = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $data = json_decode($json, true);

            // Garante que o schema venha como string formatada para o textarea
            $schema = is_array($data['json_schema_saida'] ?? null)
                ? json_encode($data['json_schema_saida'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : ($data['json_schema_saida'] ?? '');

            return response()->json([
                'sucesso' => true,
                'prompt_sistema' => $data['prompt_sistema'] ?? '',
                'json_schema_saida' => $schema
            ]);
        } catch (\Exception $e) {
            return response()->json(['sucesso' => false, 'erro' => $e->getMessage()], 500);
        }
    }
}
