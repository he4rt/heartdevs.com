<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Actions;

use He4rt\Streaming\Broadcasting\AlertTriggered;
use He4rt\Streaming\DTOs\IncomingStreamEvent;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;

final readonly class RecordStreamEvent
{
    public function __construct(
        private ResolveActiveSource $resolveActiveSource,
    ) {}

    public function handle(IncomingStreamEvent $incoming): ?StreamEvent
    {
        $source = $this->resolveActiveSource->handle($incoming->platform, $incoming->broadcasterId);

        if (!$source instanceof StreamerSource) {
            return null;
        }

        $event = StreamEvent::query()->createOrFirst(
            [
                'external_identity_id' => $source->external_identity_id,
                'source_event_id' => $incoming->sourceEventId,
            ],
            [
                'streamer_id' => $source->streamer_id,
                'stream_session_id' => $source->openSession()?->getKey(),
                'type' => $incoming->type,
                'actor_platform_id' => $incoming->actor?->platformId,
                'actor_login' => $incoming->actor?->login,
                'actor_display_name' => $incoming->actor?->displayName,
                'details' => $incoming->details,
                'occurred_at' => $incoming->occurredAt,
            ],
        );

        $shouldAlert = $event->wasRecentlyCreated
            && $source->enabled
            && $source->streamer->settings->alerts->isEnabled($event->type);

        if ($shouldAlert) {
            event(AlertTriggered::fromEvent($event));
        }

        return $event;
    }
}
