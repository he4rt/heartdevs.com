<?php

declare(strict_types=1);

namespace He4rt\Moderation;

use He4rt\Moderation\Adapters\WebModerationAdapter;
use He4rt\Moderation\Events\ActionExecuted;
use He4rt\Moderation\Events\CaseCreated;
use He4rt\Moderation\Events\CaseResolved;
use He4rt\Moderation\Listeners\RecordAuditLog;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ModerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/moderation.php', 'moderation');

        $this->app->singleton(WebModerationAdapter::class);
        $this->app->tag([WebModerationAdapter::class], 'moderation.platforms');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Event::listen(CaseCreated::class, [RecordAuditLog::class, 'handleCaseCreated']);
        Event::listen(CaseResolved::class, [RecordAuditLog::class, 'handleCaseResolved']);
        Event::listen(ActionExecuted::class, [RecordAuditLog::class, 'handleActionExecuted']);
    }
}
