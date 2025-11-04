<?php

namespace He4rt\Admin\Plugins;

use App\Providers\Filament\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;

class GuestPanelPlugin implements Plugin
{

    public function getId(): string
    {
        return FilamentPanel::Guest->value;
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
