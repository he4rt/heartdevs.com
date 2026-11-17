<?php

declare(strict_types=1);

use He4rt\PanelApp\Auth\Http\Controllers\SocialLoginCallbackController;
use He4rt\PanelApp\Auth\Http\Controllers\SocialLoginRedirectController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth/{provider}')
    ->name('social-login.')
    ->middleware('guest')
    ->group(function (): void {
        Route::get('/', SocialLoginRedirectController::class)->name('redirect');
        Route::get('/callback', SocialLoginCallbackController::class)->name('callback');
    });
