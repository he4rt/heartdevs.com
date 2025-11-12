<?php

declare(strict_types=1);

namespace He4rt\Core\Providers;

use Illuminate\Support\ServiceProvider;

class He4rtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'he4rt');
    }

    public function boot(): void {}
}
