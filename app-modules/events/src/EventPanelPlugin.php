<?php

declare(strict_types=1);

namespace He4rt\Events;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Events\Filament\Events\EventLandingPage;
use He4rt\Events\Filament\Events\ParticipantDashboard;

class EventPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Event->moduleName('core');
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            EventLandingPage::class,
            ParticipantDashboard::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
