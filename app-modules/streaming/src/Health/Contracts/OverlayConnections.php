<?php

declare(strict_types=1);

namespace He4rt\Streaming\Health\Contracts;

use He4rt\Streaming\Streamer\Models\Streamer;

interface OverlayConnections
{
    /**
     * Returns null when the realtime server does not answer.
     */
    public function count(Streamer $streamer): ?int;
}
