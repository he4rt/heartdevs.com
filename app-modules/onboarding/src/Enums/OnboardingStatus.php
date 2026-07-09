<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Enums;

use App\Enums\Concerns\StringifyEnum;

enum OnboardingStatus: string
{
    use StringifyEnum;

    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Rejected = 'rejected';

    /**
     * The statuses this status may transition into.
     *
     * @return array<int, OnboardingStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::InProgress => [self::Paused, self::Completed, self::Rejected],
            self::Paused => [self::InProgress],
            self::Completed, self::Rejected => [],
        };
    }

    public function canTransitionTo(OnboardingStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }
}
