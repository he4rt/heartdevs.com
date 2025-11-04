<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Sponsors\AdminSponsorPanelPlugin;
use Illuminate\Support\ServiceProvider;

class SponsorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminSponsorPanelPlugin),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
