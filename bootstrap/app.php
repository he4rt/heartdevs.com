<?php

declare(strict_types=1);

use App\Http\Middleware\PrepareDiscordActivityContext;
use App\Http\Middleware\SetApplicationLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )->withMiddleware(static function (Middleware $middleware): void {
        // Local, testando via túnel (cloudflared/ngrok), a conexão chega via loopback,
        // fora dos IPs publicados da Cloudflare: sem confiar em '*', o Laravel não vê
        // X-Forwarded-Proto e gera url()/asset() em http://, que o túnel recusa.
        if (env('APP_ENV') === 'local') {
            $middleware->trustProxies(at: '*');
        } else {
            $middleware->replace(
                TrustProxies::class,
                Monicahq\Cloudflare\Http\Middleware\TrustProxies::class
            );
        }

        $middleware->web(append: [
            SetApplicationLocale::class,
        ]);

        // Middleware de rota (não do grupo `web`), mas precisa rodar antes do
        // StartSession pra afetar o cookie que ele cria.
        $middleware->prependToPriorityList(
            before: StartSession::class,
            prepend: PrepareDiscordActivityContext::class,
        );
    })
    ->withExceptions(static function (Exceptions $exceptions): void {})
    ->create();
