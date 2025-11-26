<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class DlqController extends Controller
{
    /**
     * Exibe a lista de erros da DLQ.
     */
    public function index()
    {
        // Lê todos os itens da lista 'fila_dlq_erros' (do índice 0 ao -1)
        // Nota: O Redis retorna strings JSON, precisamos decodificar.
        $rawErrors = Redis::lrange('fila_dlq_erros', 0, -1);

        $errors = collect($rawErrors)->map(function ($item) {
            return json_decode($item, true);
        });

        return view('admin.dlq.index', ['errors' => $errors]);
    }

    /**
     * Limpa toda a lista DLQ.
     */
    public function clear()
    {
        Redis::del('fila_dlq_erros');
        return redirect()->back()->with('success', 'Fila de erros limpa com sucesso!');
    }
}
