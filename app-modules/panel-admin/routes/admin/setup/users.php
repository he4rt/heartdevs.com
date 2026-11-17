<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\Setup\Users\Http\Controllers\UserController;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Controllers\UserImpersonationController;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Controllers\UserOptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('/users')
    ->name('users.')
    ->controller(UserController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/lookup', [UserOptionController::class, 'index'])->name('lookup');
        Route::post('/', 'store')->name('store');
        Route::put('/{user}', 'update')->whereUuid('user')->name('update');
        Route::delete('/{user}', 'destroy')->whereUuid('user')->name('destroy');
        Route::post('/{user}/impersonate', [UserImpersonationController::class, 'store'])->whereUuid('user')->name('impersonate');
    });
