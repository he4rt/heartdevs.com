<?php

declare(strict_types=1);

namespace He4rt\Streaming;

use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Health\ReverbOverlayConnections;
use He4rt\Streaming\Streamer\Listeners\RegisterSourceOnIdentityConnected;
use He4rt\Streaming\Streamer\Listeners\SyncStreamerWithRole;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class StreamingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OverlayConnections::class, ReverbOverlayConnections::class);
    }

    public function boot(): void
    {
        Event::listen([RoleAttachedEvent::class, RoleDetachedEvent::class], SyncStreamerWithRole::class);
        Event::listen(ExternalIdentityConnected::class, [RegisterSourceOnIdentityConnected::class, 'handle']);
    }
}
