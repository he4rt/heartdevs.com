<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\System\Activity\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;

Route::prefix('/activity')
    ->name('activity.')
    ->controller(ActivityController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{activity}', 'show')->whereNumber('activity')->name('show');
    });
