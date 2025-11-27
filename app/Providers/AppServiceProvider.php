<?php

declare(strict_types=1);

namespace App\Providers;

use App\Providers\Tools\DebugbarServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDebugbar();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDatabase();
        $this->configureDates();
        $this->configureVite();
        $this->configureUrl();
    }

    /**
     * Configure the application's models
     */
    private function configureDatabase(): void
    {
        Model::automaticallyEagerLoadRelationships();
    }

    /**
     * Configure the application's Vite
     */
    private function configureVite(): void
    {
        Vite::useAggressivePrefetching();
    }

    /**
     * Configure the dates.
     */
    private function configureDates(): void
    {
        //        Date::use(CarbonImmutable::class);
    }

    /**
     * Configure the application's URL
     */
    private function configureUrl(): void
    {
        URL::forceHttps($this->app->isProduction());
    }

    private function registerDebugbar(): void
    {
        if (app()->isLocal() && class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
            $this->app->register(DebugbarServiceProvider::class);
        }
    }
}
