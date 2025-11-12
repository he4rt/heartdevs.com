<?php

declare(strict_types=1);

namespace He4rt\Events;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Events\Filament\App\EventModels\EventModelResource;

class AppEventPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::User->moduleName('event');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            EventModelResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
