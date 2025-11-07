<?php

declare(strict_types=1);

namespace He4rt\Character\Providers;

use He4rt\Character\Contracts\CharacterRepository;
use He4rt\Character\Repositories\CharacterEloquentRepository;
use Illuminate\Support\ServiceProvider;

class CharacterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CharacterRepository::class, CharacterEloquentRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
