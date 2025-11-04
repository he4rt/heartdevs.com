<?php

declare(strict_types=1);

use App\Http\Middleware\BotAuthentication;
use App\Http\Middleware\VerifyIfHasTenantProviderMiddleware;
use He4rt\Character\Http\Controllers\CharactersController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(['api', BotAuthentication::class, VerifyIfHasTenantProviderMiddleware::class])->group(function (): void {
    Route::prefix('characters')->group(function (): void {
        Route::get('/', [CharactersController::class, 'getCharacters'])
            ->name('characters.getCharacters');
        Route::get('/{provider}', [CharactersController::class, 'getCharacter'])
            ->name('characters.getCharacter');
        Route::post('/{provider}/{providerId}/daily', [CharactersController::class, 'postDailyBonus'])
            ->name('characters.dailyReward');
        Route::post('/{provider}/{providerId}/claimBadge', [CharactersController::class, 'postClaimBadge'])
            ->name('characters.claimBadge');
    });
});
