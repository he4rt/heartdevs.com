<?php

declare(strict_types=1);

namespace He4rt\Events;

use Filament\Panel;
use He4rt\Events\Filament\Resources\Events\EventResource;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            $panel->resources([
                EventResource::class,
            ]);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'events');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'events');
    }
}
