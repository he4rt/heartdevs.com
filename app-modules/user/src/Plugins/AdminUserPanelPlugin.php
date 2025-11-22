<?php

declare(strict_types=1);

namespace He4rt\User\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\User\Filament\Admin\Resources\Users\UserResource;
use He4rt\User\Filament\Shared\Widgets\ActiveEventsStats;
use He4rt\User\Filament\Shared\Widgets\UsersStatsOverview;

class AdminUserPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('user');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            UserResource::class,
        ]);

        $panel->widgets([
            UsersStatsOverview::class,
            ActiveEventsStats::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
