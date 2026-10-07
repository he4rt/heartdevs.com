<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A tag foi concedida direto por uma líder ou super admin.
 */
final readonly class DelasTagGranted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $requestId,
        public string $userId,
        public string $actorId,
    ) {}
}
