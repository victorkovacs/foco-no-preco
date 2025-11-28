<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use App\Models\GlobalLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VendedorController extends Controller
{
    // ... (métodos index e update mantidos iguais) ...
    public function index(Request $request)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $query = Vendedor::where('id_organizacao', $id_organizacao);
        if ($request->filled('search')) $query->where('NomeVendedor', 'like', '%' . $request->search . '%');
        if ($request->filled('filter_status')) {
            if ($request->filter_status === 'ativos') $query->where('Ativo', 1);
            elseif ($request->filter_status === 'inativos') $query->where('Ativo', 0);
        }
        $sortColumn = $request->get('sort', 'Ativo');
        $sortDirection = $request->get('dir', 'DESC');
        $allowedColumns = ['ID_Vendedor', 'NomeVendedor', 'SeletorPreco', 'Ativo', 'PercentualDescontoAVista', 'SeletorMarca', 'FiltroLinkProduto'];
        if (in_array($sortColumn, $allowedColumns)) $query->orderBy($sortColumn, $sortDirection);

        $query->addSelect([
            'link_exemplo' => GlobalLink::select('link')
                ->whereColumn('ID_Vendedor', 'Vendedores.ID_Vendedor')
                ->where('link', 'LIKE', '%http%')
                ->limit(1)
        ]);

        $vendedores = $query->paginate(15)->withQueryString();
        return view('vendedores.index', compact('vendedores'));
    }

    public function update(Request $request, $id)
    {
        $id_organizacao = Auth::user()->id_organizacao;
        $request->validate([
            'SeletorPreco' => 'nullable|string|max:255',
            'PercentualDescontoAVista' => 'nullable|numeric|min:0|max:100',
            'FiltroLinkProduto' => 'nullable|string|max:255',
            'LinkConcorrente' => 'nullable|url|max:255',
            'Ativo' => 'required|in:0,1',
        ]);

        $vendedor = Vendedor::where('ID_Vendedor', $id)
            ->where('id_organizacao', $id_organizacao)
            ->firstOrFail();

        $vendedor->SeletorPreco = $request->SeletorPreco;
        $vendedor->PercentualDescontoAVista = $request->PercentualDescontoAVista;
        $vendedor->FiltroLinkProduto = $request->FiltroLinkProduto;
        $vendedor->LinkConcorrente = $request->LinkConcorrente;
        $vendedor->Ativo = (int)$request->Ativo;
        $vendedor->save();

        return response()->json(['success' => true, 'message' => 'Concorrente atualizado com sucesso!']);
    }

    // --- MÉTODO DE TESTE CORRIGIDO ---
    public function testarSeletor(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'seletor_preco' => 'nullable|string',
            'desconto' => 'nullable|numeric|min:0|max:100', // [NOVO] Validação do desconto
        ]);

        $url = $request->input('url');
        $seletorPreco = $request->input('seletor_preco');
        $desconto = $request->input('desconto') ?? 0; // [NOVO] Pega o desconto ou usa 0

        $scriptPath = base_path('python_services/scraping/test_selectors.py');

        if (!file_exists($scriptPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Script de teste não encontrado em: ' . $scriptPath
            ]);
        }

        // Monta o comando passando o desconto real
        $process = new Process([
            'python3',
            $scriptPath,
            $url,           // URL Busca
            $url,           // URL Produto
            'null',         // SelMarca
            $seletorPreco ?: 'null', // SelPreco
            'null',         // SelNome
            (string)$desconto // [CORREÇÃO] Passa o valor do desconto aqui
        ]);

        $process->setTimeout(45);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();
            $json = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resposta inválida do robô.',
                    'raw_output' => $output
                ]);
            }

            return response()->json($json);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar teste: ' . $e->getMessage()
            ]);
        }
    }
}
