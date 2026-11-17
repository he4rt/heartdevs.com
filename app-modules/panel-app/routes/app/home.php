<?php

declare(strict_types=1);

use He4rt\PanelApp\Features\Home\Http\Controllers\DashboardController;
use He4rt\PanelApp\Features\Home\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('welcome');
Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
