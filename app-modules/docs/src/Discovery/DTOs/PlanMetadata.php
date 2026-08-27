<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

use He4rt\Docs\Discovery\Enums\PlanStatus;

/**
 * Type-specific metadata for a Plan, derived from its task checkboxes.
 */
final readonly class PlanMetadata
{
    public function __construct(
        public PlanStatus $status,
        public int $completedSteps,
        public int $totalSteps,
    ) {}

    /**
     * Completion percentage (0-100).
     */
    public function progress(): int
    {
        if ($this->totalSteps <= 0) {
            return 0;
        }

        return (int) round(($this->completedSteps / $this->totalSteps) * 100);
    }
}
