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
        // PRIORIDADE: Tenta ler do Docker Secret. Se falhar, tenta do .env
        $this->token = $this->getDockerSecret('sentry_auth_token') ?? env('SENTRY_AUTH_TOKEN');

        $this->org = env('SENTRY_ORG_SLUG');
        $this->project = env('SENTRY_PROJECT_SLUG');
    }

    /**
     * Função auxiliar para ler Docker Secrets em PHP
     */
    private function getDockerSecret($name)
    {
        $path = "/run/secrets/{$name}";
        if (file_exists($path)) {
            return trim(file_get_contents($path));
        }
        return null;
    }

    public function getLatestIssues($limit = 6)
    {
        // Se não tiver token (nem no secret, nem no env), retorna vazio
        if (!$this->token || !$this->org || !$this->project) {
            return [];
        }

        return Cache::remember('sentry_latest_issues', 60, function () use ($limit) {
            try {
                $response = Http::withToken($this->token)
                    ->get("{$this->baseUrl}/projects/{$this->org}/{$this->project}/issues/", [
                        'limit' => $limit,
                        'query' => 'is:unresolved',
                        'statsPeriod' => '24h',
                        'sort' => 'date',
                    ]);

                return $response->successful() ? $response->json() : [];
            } catch (\Exception $e) {
                return [];
            }
        });
    }
}
