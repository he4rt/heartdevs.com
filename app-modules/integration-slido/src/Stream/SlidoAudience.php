<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Stream;

use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class SlidoAudience
{
    public function __construct(
        private OverlayConnections $connections,
    ) {}

    /**
     * Groups the streamers that need each Slido event right now, keyed by event hash.
     *
     * A streamer counts when the account is active, the He4rt Pub scene has a Slido event and an
     * overlay is open. An unknown overlay count keeps the streamer in, so a Reverb outage does not
     * drop the questions of a live event.
     *
     * @return array<string, list<Streamer>>
     */
    public function current(): array
    {
        $audience = [];

        $streamers = Streamer::query()
            ->where('status', StreamerStatus::Active)
            ->whereNotNull(sprintf('settings->scenes->%s->slido_event_hash', OverlayScene::Pub->value))
            ->get();

        foreach ($streamers as $streamer) {
            $eventHash = $streamer->settings->pub->slidoEventHash;
            $hasOverlayClosed = $this->connections->count($streamer) === 0;

            if ($eventHash === null || $hasOverlayClosed) {
                continue;
            }

            $audience[$eventHash][] = $streamer;
        }

        return $audience;
    }
}
