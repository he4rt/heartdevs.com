<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\DTOs;

use Carbon\CarbonInterface;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\Enrollment\Models\Enrollment;

final readonly class ManualCheckInDTO
{
    public function __construct(
        public Enrollment $enrollment,
        public string $actorUserId,
        public CarbonInterface $eventDate,
    ) {
        throw_if(blank($this->actorUserId), CheckInException::invalidCheckInActor());
    }
}
