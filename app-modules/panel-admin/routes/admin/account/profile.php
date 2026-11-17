<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\Account\Profile\Http\Controllers\BrowserSessionController;
use He4rt\PanelAdmin\Features\Account\Profile\Http\Controllers\ProfileController;
use He4rt\PanelAdmin\Features\Account\Profile\Http\Controllers\ProfilePasswordController;
use He4rt\PanelAdmin\Features\Account\Profile\Http\Controllers\ProfilePreferencesController;
use Illuminate\Support\Facades\Route;

Route::prefix('/profile')
    ->name('profile.')
    ->group(function (): void {
        Route::get('/', [ProfileController::class, 'edit'])->middleware('password.confirm')->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfilePasswordController::class, 'update'])->name('password');
        Route::put('/preferences', [ProfilePreferencesController::class, 'update'])->name('preferences');
        Route::delete('/sessions', [BrowserSessionController::class, 'destroy'])->name('sessions.destroy');
    });
