<?php

declare(strict_types=1);

use He4rt\IntegrationGithub\Webhook\GithubWebhookController;
use He4rt\IntegrationGithub\Webhook\VerifyGithubSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('api/webhooks/github')
    ->middleware([VerifyGithubSignature::class])
    ->group(function (): void {
        Route::post('/', GithubWebhookController::class)->name('github.webhook');
    });
