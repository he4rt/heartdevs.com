<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class EnrollmentWaitlisted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $enrollmentId,
        public string $eventId,
        public string $userId,
        public int $waitlistPosition,
    ) {}
}
