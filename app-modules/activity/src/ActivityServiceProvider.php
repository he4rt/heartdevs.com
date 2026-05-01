<?php

declare(strict_types=1);

namespace He4rt\Activity;

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Voice\Models\Voice;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/activity-tracking.php', 'activity-tracking');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'message' => Message::class,
            'voice' => Voice::class,
        ]);
    }
}
