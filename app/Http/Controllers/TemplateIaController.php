<?php

namespace App\Http\Controllers;

use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateIaController extends Controller
{
    public function index()
    {
        return view('templates_ia.index');
    }

    public function list()
    {
        // ✅ CORREÇÃO: Filtra pela organização
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
            'json_schema_saida' => 'nullable|string',
        ]);

        $id = $request->input('id');
        $id_organizacao = Auth::user()->id_organizacao;

        if ($id) {
            // ✅ CORREÇÃO: Garante que o template pertence à organização
            $template = TemplateIa::where('id', $id)
                ->where('id_organizacao', $id_organizacao)
                ->first();

            if (!$template) {
                return response()->json(['success' => false, 'message' => 'Template não encontrado ou acesso negado.'], 404);
            }

            // Protege contra alteração maliciosa do id_organizacao
            $dados = $request->except(['id_organizacao']);
            $template->update($dados);
            $message = 'Template atualizado com sucesso!';
        } else {
            $dados = $request->all();
            // ✅ CORREÇÃO: Força a organização atual
            $dados['id_organizacao'] = $id_organizacao;
            TemplateIa::create($dados);
            $message = 'Template criado com sucesso!';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function show($id)
    {
        // ✅ CORREÇÃO: Busca segura
        $template = TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->first();

        if (!$template) return response()->json(['message' => 'Não encontrado'], 404);

        return response()->json($template);
    }

    public function destroy($id)
    {
        // ✅ CORREÇÃO: Delete seguro
        $deleted = TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'Template excluído.']);
        }
        return response()->json(['success' => false, 'message' => 'Erro ao excluir.'], 404);
    }
}
