<?php

declare(strict_types=1);

namespace He4rt\Events;

use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Events\Filament\Resources\Events\EventResource;

class AdminEventPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'admin-event';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            EventResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
