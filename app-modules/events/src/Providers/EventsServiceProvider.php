<?php

declare(strict_types=1);

namespace He4rt\Events\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Events\AdminEventPanelPlugin;
use He4rt\Events\AppEventPanelPlugin;
use He4rt\Events\EventPanelPlugin;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminEventPanelPlugin),
                FilamentPanel::User => $panel->plugin(new AppEventPanelPlugin),
                FilamentPanel::Event => $panel->plugin(new EventPanelPlugin()),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'events');
    }
}
