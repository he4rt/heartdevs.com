<?php

declare(strict_types=1);

namespace He4rt\Activity\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Activity\Filament\Admin\Resources\Messages\MessageResource;
use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->resources([
                    MessageResource::class,
                ]),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
