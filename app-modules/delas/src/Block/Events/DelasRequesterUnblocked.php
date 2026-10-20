<?php

declare(strict_types=1);

namespace He4rt\Delas\Block\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Um bloqueio foi encerrado.
 */
final readonly class DelasRequesterUnblocked implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $blockId,
        public string $userId,
        public string $actorId,
    ) {}
}
