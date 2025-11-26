<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SentryService
{
    protected $token;
    protected $org;
    protected $project;
    protected $baseUrl = 'https://sentry.io/api/0';

    public function __construct()
    {
        $this->token = env('SENTRY_AUTH_TOKEN');
        $this->org = env('SENTRY_ORG_SLUG');
        $this->project = env('SENTRY_PROJECT_SLUG');
    }

    /**
     * Busca os últimos erros não resolvidos.
     * Cache de 60 segundos para não deixar o painel lento.
     */
    public function getLatestIssues($limit = 6)
    {
        if (!$this->token || !$this->org || !$this->project) {
            return [];
        }

        return Cache::remember('sentry_latest_issues', 60, function () use ($limit) {
            try {
                $response = Http::withToken($this->token)
                    ->get("{$this->baseUrl}/projects/{$this->org}/{$this->project}/issues/", [
                        'limit' => $limit,
                        'query' => 'is:unresolved', // Apenas erros abertos
                        'statsPeriod' => '24h',     // Estatísticas das últimas 24h
                        'sort' => 'date',           // Mais recentes primeiro
                    ]);

                return $response->successful() ? $response->json() : [];
            } catch (\Exception $e) {
                return [];
            }
        });
    }
}
