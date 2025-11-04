<?php

declare(strict_types=1);

namespace He4rt\Events\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Events\AdminEventPanelPlugin;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminEventPanelPlugin),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
