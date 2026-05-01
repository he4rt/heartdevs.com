<?php

declare(strict_types=1);

namespace He4rt\Economy;

use Illuminate\Support\ServiceProvider;

class EconomyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
