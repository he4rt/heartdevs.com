<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Enums;

use App\Enums\Concerns\StringifyEnum;

enum OnboardingStepStatus: string
{
    use StringifyEnum;

    case Pending = 'pending';
    case Done = 'done';
}
