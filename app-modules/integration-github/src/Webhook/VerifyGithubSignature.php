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
        $signature = $request->header('X-Hub-Signature-256');

        abort_if($signature === null, 403, 'Missing X-Hub-Signature-256');

        $secret = config()->string('services.github.webhook_secret');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($expected, $signature), 403, 'Invalid signature');

        return $next($request);
    }
}
