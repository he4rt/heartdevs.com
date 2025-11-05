<?php

declare(strict_types=1);

namespace He4rt\User\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;

class GuestUserPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Guest->moduleName('user');
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
