<?php

declare(strict_types=1);

namespace He4rt\Events;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Events\Filament\Admin\Resources\Events\EventResource;
use He4rt\Events\Filament\Admin\Resources\Talks\TalkResource;
use He4rt\Events\Filament\Shared\Widgets\ActiveEventsStats;

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

        $panel->widgets([
            ActiveEventsStats::class,
        ]);

    }

    public function boot(Panel $panel): void {}
}
