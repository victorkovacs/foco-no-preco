<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth; // Importante
use App\Services\SentryService;

class DlqController extends Controller
{
    public function index(Request $request, SentryService $sentryService)
    {
        // ✅ CORREÇÃO: Apenas Admin pode ver logs de erro do sistema
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Acesso não autorizado.');
        }

        // 1. Busca Erros da Aplicação (Sentry Cloud)
        $sentryIssues = $sentryService->getLatestIssues(6);

        // 2. Busca Erros de Processamento (Redis DLQ)
        $perPage = 15;
        $page = $request->input('page', 1);
        $safetyLimit = 1000;
        $key = 'fila_dlq_erros';

        $totalRedis = Redis::llen($key);
        $rawErrors = Redis::lrange($key, 0, $safetyLimit);

        $collection = collect($rawErrors)->map(function ($item) {
            return json_decode($item, true);
        });

        $itemsAtual = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $errors = new LengthAwarePaginator(
            $itemsAtual,
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.dlq.index', [
            'errors' => $errors,
            'totalRedis' => $totalRedis,
            'safetyLimit' => $safetyLimit,
            'sentryIssues' => $sentryIssues
        ]);
    }

    public function clear()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        Redis::del('fila_dlq_erros');
        return redirect()->back()->with('success', 'Fila de erros limpa com sucesso!');
    }
}
