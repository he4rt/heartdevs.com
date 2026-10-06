<?php

declare(strict_types=1);

namespace He4rt\Delas;

use Illuminate\Support\ServiceProvider;

class DelasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/delas.php', 'delas');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'delas');
    }
}
