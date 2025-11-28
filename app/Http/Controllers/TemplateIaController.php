<?php

namespace App\Http\Controllers;

use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TemplateIaController extends Controller
{
    // --- O ERRO ESTAVA AQUI ---
    public function index()
    {
        // 1. Buscamos os dados no banco
        $templates = TemplateIa::where('id_organizacao', Auth::user()->id_organizacao)
            ->orderBy('nome_template')
            ->paginate(10);

        // 2. ENVIAMOS a variável $templates para a view usando compact()
        // Sem isso, a view não sabe o que é "$templates" e dá o erro 500
        return view('templates_ia.index', compact('templates'));
    }

    public function list()
    {
        $templates = TemplateIa::where('id_organizacao', Auth::user()->id_organizacao)
            ->orderBy('nome_template')
            ->get();
        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome_template' => 'required|string|max:255',
            'prompt_sistema' => 'required|string',
        ]);

        $id_organizacao = Auth::user()->id_organizacao;
        $dados = $request->only(['nome_template', 'prompt_sistema', 'json_schema_saida']);
        $dados['id_organizacao'] = $id_organizacao;
        $dados['ativo'] = true;

        if ($request->filled('id')) {
            $template = TemplateIa::where('id', $request->input('id'))
                ->where('id_organizacao', $id_organizacao)
                ->firstOrFail();
            $template->update($dados);
        } else {
            TemplateIa::create($dados);
        }

        return response()->json(['success' => true, 'message' => 'Salvo com sucesso!']);
    }

    public function show($id)
    {
        $template = TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->firstOrFail();
        return response()->json($template);
    }

    public function destroy($id)
    {
        TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->delete();

        return redirect()->route('templates_ia.index')
            ->with('success', 'Template excluído com sucesso!');
    }

    public function gerarPromptAutomatico(Request $request)
    {
        $request->validate(['exemplo_saida' => 'required|string|min:10']);
        $apiKey = config('services.google.key') ?? env('GEMINI_API_KEY');

        if (!$apiKey) return response()->json(['sucesso' => false, 'erro' => 'API Key ausente.'], 500);

        $prompt = "ATUAÇÃO: Engenheiro de Prompt Sênior.\nTAREFA: Crie um prompt_sistema e json_schema_saida para o exemplo:\n" . $request->exemplo_saida . "\nRetorne JSON.";

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json']
            ]);

            $json = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $data = json_decode($json, true);
            $schema = is_array($data['json_schema_saida'] ?? null) ? json_encode($data['json_schema_saida'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($data['json_schema_saida'] ?? '');

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
