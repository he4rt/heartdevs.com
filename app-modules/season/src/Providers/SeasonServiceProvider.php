<?php

declare(strict_types=1);

namespace He4rt\Season\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Season\AdminSeasonPanelPlugin;
use He4rt\Season\Contracts\SeasonRepository;
use He4rt\Season\Repositories\SeasonEloquentRepository;
use Illuminate\Support\ServiceProvider;

class SeasonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SeasonRepository::class, SeasonEloquentRepository::class);

        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminSeasonPanelPlugin),
                default => null,
            };
        });
    }

    public function boot(): void {}
}
