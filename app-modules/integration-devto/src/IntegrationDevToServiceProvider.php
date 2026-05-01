<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo;

use He4rt\IntegrationDevTo\Polling\SyncDevToArticles;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class IntegrationDevToServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/integration-devto.php', 'integration-devto');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncDevToArticles::class,
            ]);

            $this->app->booted(function (): void {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('devto:sync-articles')->everyThirtyMinutes();
            });
        }
    }
}
