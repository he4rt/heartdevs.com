<?php

declare(strict_types=1);

use He4rt\Events\Http\Controllers\Mobile\MobileEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/mobile')
    ->middleware(['api', 'auth:api'])
    ->group(static function (): void {
        Route::get('/events', [MobileEventController::class, 'index'])
            ->name('mobile.events.index');

        Route::get('/events/{event}', [MobileEventController::class, 'show'])
            ->name('mobile.events.show');

        Route::post('/events/{event}/enroll', [MobileEventController::class, 'enroll'])
            ->name('mobile.events.enroll');

        Route::post('/events/{event}/check-in', [MobileEventController::class, 'checkIn'])
            ->name('mobile.events.check-in');
    });
