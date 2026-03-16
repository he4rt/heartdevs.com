<?php

declare(strict_types=1);

use App\Http\Middleware\BotAuthentication;
use App\Http\Middleware\VerifyIfHasTenantProviderMiddleware;
use He4rt\Identity\ExternalIdentity\Http\Controllers\ProvidersController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(['api', BotAuthentication::class, VerifyIfHasTenantProviderMiddleware::class])->group(function (): void {
    Route::post('/providers/{provider}', [ProvidersController::class, 'postProvider'])
        ->name('providers.store');
});
