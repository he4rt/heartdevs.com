<?php

declare(strict_types=1);

namespace He4rt\Events;

use He4rt\Events\CheckIn\Listeners\GenerateQrTokenOnConfirmed;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'events');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'events');

        Event::listen(EnrollmentConfirmed::class, GenerateQrTokenOnConfirmed::class);
    }
}
