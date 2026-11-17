<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\Setup\Teams\Http\Controllers\TeamController;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Controllers\TeamRestorationController;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Controllers\TrashedTeamController;
use He4rt\PanelAdmin\Features\Setup\Teams\Members\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('/teams')
    ->name('teams.')
    ->group(function (): void {
        Route::controller(TeamController::class)->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{team}', 'show')->whereUuid('team')->withTrashed()->name('show');
            Route::put('/{team}', 'update')->whereUuid('team')->name('update');
            Route::delete('/{team}', 'destroy')->whereUuid('team')->name('destroy');
            Route::put('/{team}/restore', [TeamRestorationController::class, 'store'])->whereUuid('team')->withTrashed()->name('restore');
            Route::delete('/{team}/force', [TrashedTeamController::class, 'destroy'])->whereUuid('team')->withTrashed()->name('force-delete');
        });

        Route::prefix('/{team}/members')
            ->whereUuid('team')
            ->name('members.')
            ->controller(TeamMemberController::class)
            ->group(function (): void {
                Route::get('/', 'index')->withTrashed()->name('index');
                Route::post('/', 'store')->name('store');
                Route::delete('/{user}', 'destroy')->whereUuid('user')->name('destroy');
            });
    });
