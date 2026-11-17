<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\System\Logs\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::prefix('/logs')
    ->name('logs.')
    ->controller(LogController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{day}/{offset}', 'show')
            ->where(['day' => '\d{4}-\d{2}-\d{2}', 'offset' => '\d+'])
            ->name('show');
    });
