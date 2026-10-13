<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Support\OverlayToken;

final readonly class ResolveOverlayToken
{
    public function handle(string $token): ?Streamer
    {
        return Streamer::query()
            ->where('overlay_token_hash', OverlayToken::hash($token))
            ->where('status', StreamerStatus::Active)
            ->first();
    }
}
