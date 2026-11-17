<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\System\Docs\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/docs')
    ->name('docs.')
    ->controller(DocsController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{path}', 'show')->where('path', '[a-z0-9-]+(/[a-z0-9-]+)*')->name('show');
    });
