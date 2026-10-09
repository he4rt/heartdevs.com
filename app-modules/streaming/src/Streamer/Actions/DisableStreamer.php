<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Events\StreamerDisabled;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class DisableStreamer
{
    public function handle(Streamer $streamer): void
    {
        if (!$streamer->isActive()) {
            return;
        }

        $streamer->update(['status' => StreamerStatus::Disabled]);

        event(new StreamerDisabled($streamer));
    }
}
