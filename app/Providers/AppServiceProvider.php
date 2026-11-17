<?php

declare(strict_types=1);

namespace App\Providers;

use App\Providers\Tools\DebugbarServiceProvider;
use App\Providers\Tools\SpatieBackupServiceProvider;
use App\Providers\Tools\TelescopeServiceProvider;
use App\Support\Activitylog\ActivityAudit;
use App\Support\FeatureFlags\FeatureRegistry;
use App\Support\FeatureFlags\StoredFeatureFlag;
use App\Support\FeatureFlags\StoredOnlyDatabaseDriver;
use App\Support\Logging\KeepEveryLog;
use App\Support\Logging\RecordQueueAndConsoleEvents;
use App\Support\Logging\WideEvent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Builder;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Spatie\Activitylog\Models\Activity;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDebugbar();
        $this->registerTelescope();
        $this->registerSpatieBackup();

        $this->app->singleton(FeatureRegistry::class);
        $this->app->scoped(WideEvent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureCommands();
        $this->configureDatabase();
        $this->configureDates();
        $this->configureVite();
        $this->configureUrl();
        $this->configureHttp();
        $this->configureActivityAudit();
        $this->configureFeatureFlags();
        $this->configureWideEvents();
    }

    /**
     * Open a wide event for every job and command, count its queries, and let an admin keep every event of one user.
     */
    private function configureWideEvents(): void
    {
        resolve(FeatureRegistry::class)->register([KeepEveryLog::class]);

        DB::listen(fn (QueryExecuted $query) => resolve(WideEvent::class)->recordQuery($query->time));
        Event::subscribe(RecordQueueAndConsoleEvents::class);
    }

    /**
     * Store flags with {@see StoredOnlyDatabaseDriver}, keyed by morph alias. A queue worker reads them fresh for every job.
     */
    private function configureFeatureFlags(): void
    {
        Feature::extend('stored-only', fn (Container $app): StoredOnlyDatabaseDriver => new StoredOnlyDatabaseDriver(
            $app->make(DatabaseManager::class),
            $app->make(Dispatcher::class),
            $app->make(Repository::class),
            'database',
            [],
        ));
        Feature::useMorphMap();

        Relation::morphMap(['feature_flags' => StoredFeatureFlag::class]);

        Event::listen(JobProcessing::class, fn () => Feature::flushCache());
    }

    /**
     * Score and sign each activity once it is stored.
     */
    private function configureActivityAudit(): void
    {
        /** @var class-string<Activity> $activityModel */
        $activityModel = config('activitylog.activity_model', Activity::class);

        $activityModel::created(static fn (Activity $activity) => resolve(ActivityAudit::class)->stamp($activity));
    }

    /**
     * Configure the application's commands
     */
    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    /**
     * Configure the application's models
     */
    private function configureDatabase(): void
    {
        Model::shouldBeStrict(!$this->app->isProduction());
        Model::automaticallyEagerLoadRelationships();
        Model::unguard();
        Builder::morphUsingUuids();
        Relation::requireMorphMap();

    }

    /**
     * Configure the application's Vite
     */
    private function configureVite(): void
    {
        if ($this->app->isProduction()) {
            Vite::useAggressivePrefetching();
        }
    }

    /**
     * Configure the dates.
     */
    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Configure the application's URL
     */
    private function configureUrl(): void
    {
        URL::forceHttps($this->app->isProduction());
    }

    /**
     * Configure Http module
     */
    private function configureHttp(): void
    {
        if ($this->app->runningUnitTests()) {
            Http::preventStrayRequests();
        }
    }

    private function registerDebugbar(): void
    {
        if ($this->app->isLocal() && class_exists(\Fruitcake\LaravelDebugbar\ServiceProvider::class)) {
            $this->app->register(DebugbarServiceProvider::class);
        }
    }

    private function registerTelescope(): void
    {
        if ($this->app->isLocal() && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    private function registerSpatieBackup(): void
    {
        if (config()->boolean('backup.enabled')) {
            $this->app->register(SpatieBackupServiceProvider::class);
        }
    }
}
