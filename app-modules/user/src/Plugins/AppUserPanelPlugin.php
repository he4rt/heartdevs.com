<?php

declare(strict_types=1);

namespace He4rt\User\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;

class AppUserPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::User->moduleName('user');
    }

    public function register(Panel $panel): void
    {
        // TODO: Implement register() method.
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
