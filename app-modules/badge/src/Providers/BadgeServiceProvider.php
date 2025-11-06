<?php

declare(strict_types=1);

namespace He4rt\Badge\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Badge\AdminBadgePanelPlugin;
use He4rt\Badge\Contracts\BadgeRepository;
use He4rt\Badge\Repositories\BadgeEloquentRepository;
use Illuminate\Support\ServiceProvider;

final class BadgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BadgeRepository::class, BadgeEloquentRepository::class);
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminBadgePanelPlugin),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
