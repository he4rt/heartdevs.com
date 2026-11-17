<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\Setup\Roles\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('/roles')
    ->name('roles.')
    ->controller(RoleController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{role}/edit', 'edit')->whereUuid('role')->name('edit');
        Route::put('/{role}', 'update')->whereUuid('role')->name('update');
        Route::delete('/{role}', 'destroy')->whereUuid('role')->name('destroy');
    });
