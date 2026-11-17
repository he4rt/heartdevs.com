<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\System\Settings\Http\Controllers\AuthenticationSettingsController;
use He4rt\PanelAdmin\Features\System\Settings\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/settings')
    ->name('settings.')
    ->group(function (): void {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/authentication', [AuthenticationSettingsController::class, 'edit'])->name('authentication.edit');
        Route::put('/authentication', [AuthenticationSettingsController::class, 'update'])->name('authentication.update');
    });
