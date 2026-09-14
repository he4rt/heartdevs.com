<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\DTOs;

final readonly class RejectApplicationDTO
{
    public function __construct(
        public string $enrollmentId,
        public string $actorId,
        public string $reason,
    ) {}
}
