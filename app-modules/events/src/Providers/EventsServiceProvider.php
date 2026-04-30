<?php

declare(strict_types=1);

namespace He4rt\Events\Providers;

use He4rt\Events\Models\EventSegment;
use He4rt\Events\Models\EventSubmission;
use He4rt\Events\Models\Sponsor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'events');

        Relation::morphMap([
            'event_submission' => EventSubmission::class,
            'event_segment' => EventSegment::class,
            'sponsor' => Sponsor::class,
        ]);
    }
}
