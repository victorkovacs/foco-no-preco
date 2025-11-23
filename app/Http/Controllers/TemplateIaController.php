<?php

namespace App\Http\Controllers;

use App\Models\TemplateIa;
use Illuminate\Http\Request;

class TemplateIaController extends Controller
{
    // 1. Carregar a View
    public function index()
    {
        return view('templates_ia.index');
    }

    // 2. API: Listar Templates
    public function list()
    {
        $templates = TemplateIa::orderBy('nome_template')->get();
        return response()->json($templates);
    }

    // 3. API: Salvar (Criar ou Editar)
    public function store(Request $request)
    {
        $request->validate([
            'nome_template' => 'required|string|max:255',
            'prompt_sistema' => 'required|string',
            'json_schema_saida' => 'nullable|string', // Aceita string JSON
        ]);

        // Verifica se é edição ou criação
        $id = $request->input('id');

        if ($id) {
            $template = TemplateIa::find($id);
            if (!$template) {
                return response()->json(['success' => false, 'message' => 'Template não encontrado.'], 404);
            }
            $template->update($request->all());
            $message = 'Template atualizado com sucesso!';
        } else {
            TemplateIa::create($request->all());
            $message = 'Template criado com sucesso!';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    // 4. API: Buscar um para Edição
    public function show($id)
    {
        $template = TemplateIa::find($id);
        return response()->json($template);
    }

    // 5. API: Excluir
    public function destroy($id)
    {
        TemplateIa::destroy($id);
        return response()->json(['success' => true, 'message' => 'Template excluído.']);
    }
}
