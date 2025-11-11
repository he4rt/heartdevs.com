<?php

declare(strict_types=1);

namespace He4rt\Message\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Message\Filament\Admin\Resources\Messages\MessageResource;

class AdminMessagePanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('message');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MessageResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
