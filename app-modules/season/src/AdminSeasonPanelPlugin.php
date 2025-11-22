<?php

declare(strict_types=1);

namespace He4rt\Season;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Season\Filament\Admin\Resources\Seasons\SeasonResource;
use He4rt\Season\Filament\Shared\Widgets\SeasonStatsOverview;

class AdminSeasonPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('season');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SeasonResource::class,
        ]);

        $panel->widgets([
            SeasonStatsOverview::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
