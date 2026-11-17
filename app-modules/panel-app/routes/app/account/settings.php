<?php

declare(strict_types=1);

use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\BrowserSessionController;
use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\PasswordController;
use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\PreferencesController;
use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\ProfileController;
use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\SecurityController;
use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\SocialAccounts\SocialAccountCallbackController;
use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\SocialAccounts\SocialAccountController;
use He4rt\PanelApp\Features\Account\Settings\Http\Controllers\SocialAccounts\SocialAccountRedirectController;
use Illuminate\Support\Facades\Route;

Route::prefix('/settings')
    ->name('settings.')
    ->middleware('auth')
    ->group(function (): void {
        Route::redirect('/', '/settings/profile')->name('index');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::middleware('verified')->group(function (): void {
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
            Route::get('/security', [SecurityController::class, 'edit'])->middleware('password.confirm')->name('security.edit');
            Route::post('/password', [PasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');
            Route::put('/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
            Route::delete('/social-accounts/{socialAccount}', [SocialAccountController::class, 'destroy'])->whereUuid('socialAccount')->name('social-accounts.destroy');
            Route::delete('/sessions', [BrowserSessionController::class, 'destroy'])->middleware('throttle:6,1')->name('sessions.destroy');
            Route::get('/preferences', [PreferencesController::class, 'edit'])->name('preferences.edit');
            Route::put('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');
            Route::inertia('/appearance', 'settings/appearance')->name('appearance.edit');
        });
    });

Route::prefix('/auth/{provider}')
    ->name('social-accounts.')
    ->middleware(['auth', 'verified'])
    ->group(function (): void {
        Route::get('/connect', SocialAccountRedirectController::class)->name('redirect');
        Route::get('/callback/connect', SocialAccountCallbackController::class)->name('callback');
    });
