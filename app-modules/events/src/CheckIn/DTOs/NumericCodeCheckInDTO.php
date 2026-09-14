<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\DTOs;

use Carbon\CarbonInterface;
use He4rt\Events\Enrollment\Models\Enrollment;

final readonly class NumericCodeCheckInDTO
{
    public function __construct(
        public Enrollment $enrollment,
        public string $code,
        public CarbonInterface $eventDate,
    ) {}
}
