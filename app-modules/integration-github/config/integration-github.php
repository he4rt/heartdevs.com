<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub API token (PAT)
    |--------------------------------------------------------------------------
    |
    | Fine-grained PAT que autentica o conector REST usado no backfill de
    | contribuições. Quando ausente, as chamadas seguem sem autenticação
    | (sujeitas ao rate limit anônimo do GitHub).
    |
    */
    'api_token' => env('GITHUB_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Webhook secret
    |--------------------------------------------------------------------------
    |
    | Shared secret do webhook da organização, usado para validar a assinatura
    | HMAC (X-Hub-Signature-256) das entregas. Sem ele, o middleware recusa as
    | requisições (fail-safe) em vez de aceitar payloads não verificados.
    |
    */
    'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
];
