<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Support\OverlayToken;

final readonly class RegenerateOverlayToken
{
    public function handle(Streamer $streamer): string
    {
        $token = OverlayToken::generate();

        $streamer->forceFill(OverlayToken::attributes($token))->save();

        return $token;
    }
}
