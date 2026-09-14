<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ParticipantCheckedIn implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $checkInId,
        public string $enrollmentId,
        public string $eventId,
        public string $userId,
        public string $eventDate,
        public int $xpRewardOnCheckedIn,
    ) {}
}
