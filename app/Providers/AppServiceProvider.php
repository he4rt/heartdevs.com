<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Paginator as PaginatorInterface;
use App\Providers\Tools\DebugbarServiceProvider;
use App\Providers\Tools\TelescopeServiceProvider;
use App\Support\Paginator;
use Carbon\CarbonImmutable;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        $this->app->bind(PaginatorInterface::class, Paginator::class);

        $this->registerDebugbar();
        $this->registerTelescope();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureCommands();
        $this->configureDatabase();
        $this->configureDates();
        $this->configureHttp();
        $this->configureVite();
        $this->configureUrl();
        $this->configureMorphMap();
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    private function configureDatabase(): void
    {
        Model::automaticallyEagerLoadRelationships();
        Model::unguard();
        Relation::requireMorphMap();
    }

    private function configureHttp(): void
    {
        if ($this->app->runningUnitTests()) {
            Http::preventStrayRequests();
        }
    }

    private function configureVite(): void
    {
        if ($this->app->isProduction()) {
            Vite::useAggressivePrefetching();
        }
    }

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function configureUrl(): void
    {
        URL::forceHttps($this->app->isProduction());
    }

    private function registerDebugbar(): void
    {
        if ($this->app->isLocal() && class_exists(\Fruitcake\LaravelDebugbar\ServiceProvider::class)) {
            $this->app->register(DebugbarServiceProvider::class);
        }
    }

    private function registerTelescope(): void
    {
        $this->app->register(TelescopeServiceProvider::class);
    }

    private function configureMorphMap(): void
    {
        Relation::morphMap([
            'user' => User::class,
        ]);
    }
}
