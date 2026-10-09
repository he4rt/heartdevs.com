<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Events;

use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class StreamerSourceRegistered implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public StreamerSource $source,
    ) {}
}
