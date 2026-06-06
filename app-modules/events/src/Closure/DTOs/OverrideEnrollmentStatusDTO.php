<?php

declare(strict_types=1);

namespace He4rt\Events\Closure\DTOs;

use He4rt\Events\Closure\Exceptions\OverrideEnrollmentStatusException;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;

final readonly class OverrideEnrollmentStatusDTO
{
    public function __construct(
        public Enrollment $enrollment,
        public EnrollmentStatus $fromStatus,
        public EnrollmentStatus $toStatus,
        public string $actorId,
        public string $reason,
    ) {
        if (mb_trim($this->reason) === '') {
            throw OverrideEnrollmentStatusException::reasonRequired();
        }
    }
}
