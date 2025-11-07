<?php

declare(strict_types=1);

namespace He4rt\Meeting\Providers;

use He4rt\Meeting\Contracts\MeetingRepository;
use He4rt\Meeting\Contracts\MeetingTypeRepository;
use He4rt\Meeting\Repositories\MeetingEloquentRepository;
use He4rt\Meeting\Repositories\MeetingTypeEloquentRepository;
use Illuminate\Support\ServiceProvider;

class MeetingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MeetingRepository::class, MeetingEloquentRepository::class);
        $this->app->bind(MeetingTypeRepository::class, MeetingTypeEloquentRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
