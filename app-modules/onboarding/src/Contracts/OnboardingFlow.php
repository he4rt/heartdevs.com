<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Contracts;

use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;

interface OnboardingFlow
{
    /**
     * The ordered step keys that make up this flow.
     *
     * @return list<string>
     */
    public function steps(): array;

    /**
     * The onboarding types that must be completed before this one can start.
     *
     * @return list<OnboardingType>
     */
    public function prerequisites(): array;

    /**
     * Move the onboarding to its next step, completing it when the flow is done.
     */
    public function advance(Onboarding $onboarding): void;

    /**
     * Whether every step of the flow has been completed.
     */
    public function isComplete(Onboarding $onboarding): bool;
}
