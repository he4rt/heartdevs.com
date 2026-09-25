<?php

declare(strict_types=1);

use App\Http\Middleware\SetApplicationLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )->withMiddleware(static function (Middleware $middleware): void {
        $middleware->replace(
            TrustProxies::class,
            Monicahq\Cloudflare\Http\Middleware\TrustProxies::class
        );

        $middleware->web(append: [
            SetApplicationLocale::class,
        ]);

        // O app não tem rota "login" — auth é só via OAuth (Filament cuida do
        // próprio redirect nos painéis). Sem isso, o middleware `auth`/`auth:api`
        // tenta redirect(route('login')) pra qualquer client sem "Accept:
        // application/json" e quebra com 500 (RouteNotFoundException).
        $middleware->redirectGuestsTo(redirect: null);
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        // Sem isso, um cliente de API que não manda "Accept: application/json"
        // (curl puro, a maioria dos clientes HTTP mobile) recebe um 500 em vez
        // de 401/erro em JSON: o handler padrão tenta redirect(route('login')),
        // que não existe neste app — login é só via OAuth.
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $throwable): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create();
