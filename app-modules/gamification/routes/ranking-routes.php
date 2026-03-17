<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Models\Character;
use Illuminate\Support\Facades\Route;

Route::middleware('api')
    ->prefix('api')
    ->group(function (): void {
        Route::get('/ranking/leveling', fn () => Character::with(['user'])
            ->orderByDesc('experience')
            ->paginate(10))->name('ranking.leveling');
    });
