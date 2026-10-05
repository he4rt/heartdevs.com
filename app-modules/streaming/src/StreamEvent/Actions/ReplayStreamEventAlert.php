<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Actions;

use He4rt\Streaming\Broadcasting\AlertTriggered;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;

/**
 * Sends a recorded alert to the overlay again, for the alert that passed while another scene was on.
 */
final readonly class ReplayStreamEventAlert
{
    public function handle(Streamer $streamer, string $eventId): StreamEvent
    {
        $event = $streamer->events()->whereKey($eventId)->firstOrFail();

        event(AlertTriggered::fromEvent($event));

        return $event;
    }
}
