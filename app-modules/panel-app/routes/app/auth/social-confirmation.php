<?php

declare(strict_types=1);

use He4rt\PanelApp\Auth\Http\Controllers\SocialConfirmationCallbackController;
use He4rt\PanelApp\Auth\Http\Controllers\SocialConfirmationRedirectController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth/{provider}')
    ->name('social-confirmation.')
    ->middleware('auth')
    ->group(function (): void {
        Route::get('/confirm', SocialConfirmationRedirectController::class)->name('redirect');
        Route::get('/callback/confirm', SocialConfirmationCallbackController::class)->name('callback');
    });
