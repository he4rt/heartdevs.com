<?php

declare(strict_types=1);

use App\Http\Controllers\SwitchLocaleController;
use App\Support\ApplicationLocale;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', SwitchLocaleController::class)
    ->whereIn('locale', ApplicationLocale::SUPPORTED)
    ->name('locale.switch');
