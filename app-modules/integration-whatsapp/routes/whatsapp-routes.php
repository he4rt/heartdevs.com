<?php

declare(strict_types=1);

use He4rt\IntegrationWhatsapp\Ingest\Http\Controllers\WhatsAppWebhookController;
use He4rt\IntegrationWhatsapp\Ingest\Http\Middleware\VerifyWhatsAppSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('/api/webhooks')
    ->middleware(['api', VerifyWhatsAppSignature::class])
    ->group(function (): void {
        Route::post('/whatsapp', [WhatsAppWebhookController::class, 'store'])
            ->name('api.webhooks.whatsapp');
    });
