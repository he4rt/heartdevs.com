<?php

declare(strict_types=1);

namespace He4rt\Season;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Season\Filament\Resources\Seasons\SeasonResource;

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
    }

    public function boot(Panel $panel): void {}
}
