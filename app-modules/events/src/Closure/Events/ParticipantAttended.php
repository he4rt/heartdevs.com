<?php

declare(strict_types=1);

namespace He4rt\Events\Closure\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ParticipantAttended implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    //  To be consumed by the gamification module
    public function __construct(
        public string $enrollmentId,
        public string $eventId,
        public string $userId,
        public int $xpRewardOnAttended,
    ) {}
}
