<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Exceptions;

use Exception;
use He4rt\Onboarding\Models\Onboarding;

final class OnboardingPausedException extends Exception
{
    public static function cannotAdvance(Onboarding $onboarding): self
    {
        return new self(
            sprintf('Cannot advance onboarding "%s" while paused.', $onboarding->id)
        );
    }
}
