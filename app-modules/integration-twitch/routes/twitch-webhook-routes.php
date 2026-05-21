<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Http\Controllers\TwitchWebhookController;
use He4rt\IntegrationTwitch\Http\Middleware\VerifyTwitchSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('api/webhooks/twitch')
    ->middleware(VerifyTwitchSignature::class)
    ->group(function (): void {
        Route::post('/eventsub', TwitchWebhookController::class)
            ->name('twitch.eventsub.webhook');
    });
