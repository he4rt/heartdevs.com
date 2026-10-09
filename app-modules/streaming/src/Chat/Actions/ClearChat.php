<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Actions;

use Carbon\CarbonImmutable;
use He4rt\Streaming\Broadcasting\ChatCleared;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class ClearChat
{
    public function handle(Streamer $streamer, CarbonImmutable $clearedAt): void
    {
        $streamer->update(['chat_cleared_at' => $clearedAt]);

        event(new ChatCleared($streamer));
    }
}
