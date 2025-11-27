<?php

namespace App\Http\Controllers;

use App\Models\TemplateIa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateIaController extends Controller
{
    // 1. Carregar a View
    public function index()
    {
        return view('templates_ia.index');
    }

    // 2. API: Listar Templates (SEGURANÇA APLICADA)
    public function list()
    {
        // ✅ CORREÇÃO: Filtra apenas pela organização do usuário logado
        $templates = TemplateIa::where('id_organizacao', Auth::user()->id_organizacao)
            ->orderBy('nome_template')
            ->get();

        return response()->json($templates);
    }

    // 3. API: Salvar (Criar ou Editar)
    public function store(Request $request)
    {
        $request->validate([
            'nome_template' => 'required|string|max:255',
            'prompt_sistema' => 'required|string',
            'json_schema_saida' => 'nullable|string',
        ]);

        $id = $request->input('id');
        $id_organizacao = Auth::user()->id_organizacao; // Pega da sessão

        if ($id) {
            // ✅ CORREÇÃO: Garante que só edita se pertencer à organização
            $template = TemplateIa::where('id', $id)
                ->where('id_organizacao', $id_organizacao)
                ->first();

            if (!$template) {
                return response()->json(['success' => false, 'message' => 'Template não encontrado ou acesso negado.'], 403);
            }

            // Impede que o usuário mude o id_organizacao via injeção de dados
            $dados = $request->except(['id_organizacao']);
            $template->update($dados);

            $message = 'Template atualizado com sucesso!';
        } else {
            // ✅ CORREÇÃO: Força o ID da organização na criação
            $dados = $request->all();
            $dados['id_organizacao'] = $id_organizacao;

            TemplateIa::create($dados);
            $message = 'Template criado com sucesso!';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    // 4. API: Buscar um para Edição
    public function show($id)
    {
        // ✅ CORREÇÃO: Impede ver dados de outro cliente
        $template = TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->first();

        if (!$template) {
            return response()->json(['message' => 'Não encontrado'], 404);
        }
        return response()->json($template);
    }

    // 5. API: Excluir
    public function destroy($id)
    {
        // ✅ CORREÇÃO: Delete seguro com escopo
        $deleted = TemplateIa::where('id', $id)
            ->where('id_organizacao', Auth::user()->id_organizacao)
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Erro: Template não encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Template excluído.']);
    }
}
