<?php

declare(strict_types=1);

namespace He4rt\Gamification\Providers;

use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Models\Character;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class GamificationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Relation::morphMap([
            'character' => Character::class,
            'badge' => Badge::class,
        ]);
    }
}
