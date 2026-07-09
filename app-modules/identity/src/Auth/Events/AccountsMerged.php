<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when two accounts are merged into one. Other modules listen to
 * reassign their user-owned records from the merged account to the survivor
 * before the merged account is deleted.
 */
final class AccountsMerged
{
    use Dispatchable;

    public function __construct(
        public string $survivorId,
        public string $mergedId,
    ) {}
}
