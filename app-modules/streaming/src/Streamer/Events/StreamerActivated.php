<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Events;

use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class StreamerActivated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Streamer $streamer,
    ) {}
}
