<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A tag foi removida de quem tinha.
 */
final readonly class DelasTagRevoked implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $requestId,
        public string $userId,
        public string $actorId,
    ) {}
}
