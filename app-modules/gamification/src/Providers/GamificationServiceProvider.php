<?php

declare(strict_types=1);

namespace He4rt\Gamification\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Gamification\Badge\Filament\Resources\Badges\BadgeResource;
use He4rt\Gamification\Season\Filament\Admin\Resources\Seasons\SeasonResource;
use He4rt\Gamification\Season\Filament\Shared\Widgets\SeasonStatsOverview;
use Illuminate\Support\ServiceProvider;

class GamificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel
                    ->resources([
                        BadgeResource::class,
                        SeasonResource::class,
                    ])
                    ->widgets([
                        SeasonStatsOverview::class,
                    ]),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
