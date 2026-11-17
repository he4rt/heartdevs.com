<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\Home\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');
