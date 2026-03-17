<?php

declare(strict_types=1);

use He4rt\Gamification\Season\Http\Controllers\SeasonsController;
use Illuminate\Support\Facades\Route;

Route::prefix('season')->group(function (): void {
    Route::get('/v2/seasons', [SeasonsController::class, 'getSeasons'])->name('get-seasons');
    Route::get('/v2/seasons/current', [SeasonsController::class, 'getCurrent'])->name('seasons.current');
});
