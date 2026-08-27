<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->middleware('web')
    ->group(static function (): void {
        Route::prefix('oauth')->group(static function (): void {
            Route::get('/{provider}', [OAuthController::class, 'getAuthenticate'])
                ->name('oauth.authenticate');

            Route::get('/{panel}/{provider}/redirect', [OAuthController::class, 'getRedirect'])
                ->name('oauth.redirect');
        });
    });
