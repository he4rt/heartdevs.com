<?php

declare(strict_types=1);

use App\Http\Middleware\RecordWideEvent;
use App\Support\Logging\WideEvent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->prepend(RecordWideEvent::class);
        $middleware->replace(
            TrustProxies::class,
            Monicahq\Cloudflare\Http\Middleware\TrustProxies::class
        );
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        $exceptions->report(static fn (Throwable $exception): ?false => resolve(WideEvent::class)->recordError($exception));
    })
    ->create();
