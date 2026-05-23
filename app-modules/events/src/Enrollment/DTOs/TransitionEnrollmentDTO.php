<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\DTOs;

use Carbon\CarbonInterface;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;

final readonly class TransitionEnrollmentDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public Enrollment $enrollment,
        public EnrollmentStatus $toStatus,
        public TriggeredBy $triggeredBy,
        public ?string $actorId = null,
        public ?string $reason = null,
        public array $metadata = [],
        public ?CarbonInterface $timestamp = null,
    ) {}
}
