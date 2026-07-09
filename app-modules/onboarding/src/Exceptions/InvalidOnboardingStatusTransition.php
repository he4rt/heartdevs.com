<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Exceptions;

use Exception;
use He4rt\Onboarding\Enums\OnboardingStatus;

final class InvalidOnboardingStatusTransition extends Exception
{
    public static function between(OnboardingStatus $from, OnboardingStatus $to): self
    {
        return new self(
            sprintf('Cannot transition onboarding from "%s" to "%s".', $from->value, $to->value)
        );
    }
}
