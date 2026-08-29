<?php

declare(strict_types=1);

namespace He4rt\Live;

use Illuminate\Support\ServiceProvider;

class LiveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/live.php', 'live');
    }
}
