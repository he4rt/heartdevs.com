<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Webhook;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyGithubSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config()->string('integration-github.webhook_secret');

        // Fail-safe: sem secret configurado, um HMAC de chave vazia seria forjável
        // por qualquer um. Recusa em vez de aceitar silenciosamente.
        abort_if($secret === '', 500, 'GitHub webhook secret is not configured');

        $signature = $request->header('X-Hub-Signature-256');

        abort_if($signature === null, 403, 'Missing X-Hub-Signature-256');

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($expected, $signature), 403, 'Invalid signature');

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
