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
}
