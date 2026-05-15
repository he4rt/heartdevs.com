<?php

declare(strict_types=1);

use He4rt\Events\Http\Controllers\GpsCheckinController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::post('events/{event}/checkin', [GpsCheckinController::class, 'gpsCheckin']);
});
