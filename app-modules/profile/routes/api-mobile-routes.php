<?php

declare(strict_types=1);

use He4rt\Profile\Http\Controllers\Mobile\MobileProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/mobile')
    ->middleware(['api', 'auth:api'])
    ->group(static function (): void {
        Route::get('/profile', MobileProfileController::class)
            ->name('mobile.profile.show');
    });
