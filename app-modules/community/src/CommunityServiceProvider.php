<?php

declare(strict_types=1);

namespace He4rt\Community;

use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CommunityServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'upcoming_event' => UpcomingEvent::class,
        ]);
    }
}
