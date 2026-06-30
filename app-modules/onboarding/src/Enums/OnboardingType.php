<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Enums;

use App\Enums\Concerns\StringifyEnum;
use He4rt\Onboarding\Contracts\OnboardingFlow;
use He4rt\Onboarding\Flows\WelcomeOnboardingFlow;

enum OnboardingType: string
{
    use StringifyEnum;

    case Welcome = 'welcome';
    case Squads = 'squads';

    public function handler(): ?OnboardingFlow
    {
        return match ($this) {
            self::Welcome => resolve(WelcomeOnboardingFlow::class),
            default => null,
        };
    }
}
