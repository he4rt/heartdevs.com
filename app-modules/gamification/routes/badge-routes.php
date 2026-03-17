<?php

declare(strict_types=1);

use App\Http\Middleware\BotAuthentication;
use App\Http\Middleware\VerifyIfHasTenantProviderMiddleware;
use He4rt\Gamification\Badge\Http\Controllers\BadgesController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api', 'middleware' => [BotAuthentication::class, VerifyIfHasTenantProviderMiddleware::class]], function (): void {
    Route::prefix('badges')->group(function (): void {
        Route::get('/', [BadgesController::class, 'getBadges'])->name('badges.index');
        Route::post('/', [BadgesController::class, 'postBadge'])->name('badges.store');
        Route::delete('/{badgeId}', [BadgesController::class, 'deleteBadge'])->name('badges.destroy');
    });
});
