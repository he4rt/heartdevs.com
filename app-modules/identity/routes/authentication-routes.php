<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Http\Controllers\OAuthController;
use He4rt\Identity\Auth\Http\Controllers\TenantLogoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->middleware('web')
    ->group(function (): void {
        Route::post('/{tenant}/logout', TenantLogoutController::class)->name('tenant.logout');

        Route::prefix('oauth')->group(function (): void {
            Route::get('/{provider}', [OAuthController::class, 'getAuthenticate'])
                ->name('oauth.authenticate');

            Route::get('/{tenant}/{panel}/{provider}/redirect', [OAuthController::class, 'getRedirect'])
                ->name('oauth.redirect');
        });
    });
