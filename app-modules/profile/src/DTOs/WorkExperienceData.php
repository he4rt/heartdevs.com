<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class WorkExperienceData
{
    public function __construct(
        public string $company,
        public string $position,
        public string $period,
        public ?string $description = null,
        public ?string $duration = null,
        public bool $isCurrent = false,
    ) {}
}
