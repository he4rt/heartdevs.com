<?php

declare(strict_types=1);

namespace He4rt\Events;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Events\Filament\Resources\Events\EventResource;
use He4rt\Events\Filament\Resources\Talks\TalkResource;

class AdminEventPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('event');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            EventResource::class,
            TalkResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
    }
}
