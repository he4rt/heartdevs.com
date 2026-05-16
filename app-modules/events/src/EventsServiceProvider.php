<?php

declare(strict_types=1);

namespace He4rt\Events;

use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'events');
    }
}
