<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Enums;

use App\Enums\Concerns\StringifyEnum;
use He4rt\Onboarding\Contracts\OnboardingFlow;
use He4rt\Onboarding\Flows\WelcomeOnboardingFlow;
use He4rt\Onboarding\Flows\SquadsOnboardingFlow;
use LogicException;

enum OnboardingType: string
{
    use StringifyEnum;

    case Welcome = 'welcome';
    case Squads = 'squads';

    public function handler(): OnboardingFlow
    {
        return match ($this) {
            self::Welcome => resolve(WelcomeOnboardingFlow::class),
            self::Squads => resolve(SquadsOnboardingFlow::class),
            default => throw new LogicException(sprintf("OnboardingFlow para o tipo '%s' ainda não foi implementado.", $this->value)),
        };
    }
}
