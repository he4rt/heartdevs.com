<?php

declare(strict_types=1);

namespace He4rt\Activity;

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Activity\Timeline\Actions\PublishModerationEntry;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Listeners\PublishModerationToTimeline;
use He4rt\Activity\Timeline\Prototype\PrototypeTimelineCommand;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Moderation\Enforcement\ActionExecuted;
use He4rt\Moderation\Enforcement\ModerationAction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/activity-tracking.php', 'activity-tracking');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PrototypeTimelineCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'message' => Message::class,
            'voice' => Voice::class,
            'post_entry' => PostEntry::class,
            'moderation_event' => ModerationEvent::class,
            'moderation_action' => ModerationAction::class,
            'timeline' => Timeline::class,
        ]);

        ModerationEvent::created(function (ModerationEvent $event): void {
            resolve(PublishModerationEntry::class)->handle($event);
        });

        Event::listen(ActionExecuted::class, [PublishModerationToTimeline::class, 'handle']);
    }
}
