<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Uma pessoa pediu a tag He4rt Delas.
 */
final readonly class DelasTagRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $requestId,
        public string $userId,
    ) {}
}
