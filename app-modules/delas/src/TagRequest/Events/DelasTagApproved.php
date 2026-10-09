<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Uma solicitação pendente foi aprovada pela moderação.
 */
final readonly class DelasTagApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $requestId,
        public string $userId,
        public string $actorId,
    ) {}
}
