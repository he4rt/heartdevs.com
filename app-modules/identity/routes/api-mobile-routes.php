<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Http\Controllers\Mobile\MobileAuthController;
use He4rt\Identity\Auth\Http\Controllers\Mobile\MobileMeController;
use He4rt\Identity\Auth\Http\Controllers\Mobile\MobileOAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/mobile')
    ->middleware('api')
    ->group(static function (): void {
        Route::prefix('auth')->group(static function (): void {
            Route::get('/{provider}/redirect', [MobileOAuthController::class, 'redirect'])
                ->name('mobile.oauth.redirect');

            Route::get('/{provider}/callback', [MobileOAuthController::class, 'callback'])
                ->name('mobile.oauth.callback');

            Route::post('/exchange', [MobileAuthController::class, 'exchange'])
                ->name('mobile.auth.exchange');

            Route::post('/refresh', [MobileAuthController::class, 'refresh'])
                ->name('mobile.auth.refresh');

            Route::post('/logout', [MobileAuthController::class, 'logout'])
                ->middleware('auth:api')
                ->name('mobile.auth.logout');
        });

        Route::get('/me', MobileMeController::class)
            ->middleware('auth:api')
            ->name('mobile.me');
    });
