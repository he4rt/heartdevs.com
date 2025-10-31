<?php

declare(strict_types=1);

namespace He4rt\Season\Providers;

use He4rt\Season\Contracts\SeasonRepository;
use He4rt\Season\Repositories\SeasonEloquentRepository;
use Illuminate\Support\ServiceProvider;

class SeasonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SeasonRepository::class, SeasonEloquentRepository::class);
    }

    public function boot(): void {}
}
