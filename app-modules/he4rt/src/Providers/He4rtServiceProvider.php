<?php

declare(strict_types=1);

namespace He4rt\Core\Providers;

use Illuminate\Support\ServiceProvider;

class He4rtServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'he4rt');
        $this->loadViewsFrom(__DIR__.'/../../resources/views/3pontos/views', '3pontos');
    }
}
