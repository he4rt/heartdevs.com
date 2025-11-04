<?php

declare(strict_types=1);

namespace He4rt\Admin\Plugins;

use App\Providers\Filament\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;

class AdminPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->value;
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
