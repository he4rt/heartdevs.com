<?php

declare(strict_types=1);

namespace He4rt\User\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Events\Filament\App\EventModels\Widgets\LatestEvents;
use He4rt\User\Filament\User\Pages\Dashboard;
use He4rt\User\Filament\User\Pages\UserProfile;

class AppUserPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::User->moduleName('user');
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            UserProfile::class,
            Dashboard::class,
        ]);
        $panel->widgets([
            LatestEvents::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
