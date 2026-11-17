<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Controllers\FeatureFlagController;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Controllers\FeatureFlagGateController;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Controllers\FeatureFlagTeamController;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Controllers\FeatureFlagUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/feature-flags')
    ->name('feature-flags.')
    ->group(function (): void {
        Route::get('/', [FeatureFlagController::class, 'index'])->name('index');

        Route::prefix('/{flag}')
            ->where(['flag' => '[a-z0-9-]+'])
            ->group(function (): void {
                Route::post('/gate', [FeatureFlagGateController::class, 'store'])->name('gate.store');
                Route::delete('/gate', [FeatureFlagGateController::class, 'destroy'])->name('gate.destroy');

                Route::controller(FeatureFlagTeamController::class)->group(function (): void {
                    Route::get('/teams', 'index')->name('teams.index');
                    Route::post('/teams/{team}', 'store')->whereUuid('team')->name('teams.store');
                    Route::delete('/teams/{team}', 'destroy')->whereUuid('team')->name('teams.destroy');
                });

                Route::controller(FeatureFlagUserController::class)->group(function (): void {
                    Route::get('/users', 'index')->name('users.index');
                    Route::post('/users/{user}', 'store')->whereUuid('user')->name('users.store');
                    Route::delete('/users/{user}', 'destroy')->whereUuid('user')->name('users.destroy');
                });
            });
    });
