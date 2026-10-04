<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Http\Controllers\Mobile\MobileTimelineController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/mobile')
    ->middleware(['api', 'auth:api'])
    ->group(static function (): void {
        Route::get('/timeline', [MobileTimelineController::class, 'index'])
            ->name('mobile.timeline.index');

        Route::post('/timeline', [MobileTimelineController::class, 'store'])
            ->name('mobile.timeline.store');

        Route::post('/timeline/{post}/replies', [MobileTimelineController::class, 'storeReply'])
            ->name('mobile.timeline.replies.store');

        Route::delete('/timeline/replies/{reply}', [MobileTimelineController::class, 'destroyReply'])
            ->name('mobile.timeline.replies.destroy');
    });
