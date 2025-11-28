<?php

use Illuminate\Support\Str;

// Função auxiliar para ler secrets (com proteção para não redeclarar)
if (!function_exists('get_docker_secret')) {
    function get_docker_secret($name, $fallbackEnv = null, $default = null)
    {
        $secretPath = "/run/secrets/{$name}";
        if (file_exists($secretPath)) {
            return trim(file_get_contents($secretPath));
        }
        if ($fallbackEnv) {
            return env($fallbackEnv, $default);
        }
        return $default;
    }
}

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),

            // --- AQUI ESTÁ A MUDANÇA ---
            // Lê do arquivo secret primeiro, se não achar, tenta o .env
            'username' => get_docker_secret('mail_username', 'MAIL_USERNAME'),
            'password' => get_docker_secret('mail_password', 'MAIL_PASSWORD'),

            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],
        // ... outros mailers ...
    ],

    'from' => [
        'address' => get_docker_secret('mail_username', 'MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Foco no Preço'),
    ],

];
