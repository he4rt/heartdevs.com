<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Events\StreamerActivated;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class ActivateStreamer
{
    public function handle(Streamer $streamer): void
    {
        if ($streamer->isActive()) {
            return;
        }

        $streamer->update(['status' => StreamerStatus::Active]);

        event(new StreamerActivated($streamer));
    }
}
