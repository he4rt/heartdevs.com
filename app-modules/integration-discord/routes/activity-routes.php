<?php

declare(strict_types=1);

use App\Http\Middleware\PrepareDiscordActivityContext;
use He4rt\IntegrationDiscord\Activity\Http\Controllers\ActivityAuthController;
use Illuminate\Support\Facades\Route;

// Sessão/CSRF exigidos aqui, diferente das rotas de ingest do módulo `live`: é o
// navegador dentro do iframe da Activity chamando, não o mediamtx server-to-server.
Route::middleware('web')->group(static function (): void {
    Route::post('discord-activity/auth', ActivityAuthController::class)
        ->middleware(PrepareDiscordActivityContext::class)
        ->name('discord-activity.auth');
});
