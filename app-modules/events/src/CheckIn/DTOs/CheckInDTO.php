<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\DTOs;

use Carbon\CarbonInterface;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;

final readonly class CheckInDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Enrollment $enrollment,
        public CheckInMethod $method,
        public array $payload,
        public CarbonInterface $eventDate,
        public string $actorUserId,
        public TriggeredBy $triggeredBy,
    ) {}
}
