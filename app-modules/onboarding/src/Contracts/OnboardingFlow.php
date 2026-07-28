<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Contracts;

use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Exceptions\OnboardingPausedException;
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
     * The DTO responsible for validating the given step's payload.
     */
    public function stepDto(string $stepKey): OnboardingStepDTO;

    /**
     * The onboarding types that must be completed before this one can start.
     *
     * @return list<OnboardingType>
     */
    public function prerequisites(): array;

    /**
     * Move the onboarding to its next step, completing it when the flow is done.
     *
     * @throws OnboardingPausedException when the onboarding is paused.
     */
    public function advance(Onboarding $onboarding): void;

    /**
     * Create the next step defined by the flow, if any.
     * Implementations may gate the creation (e.g. require a linked GitHub account).
     */
    public function createNextStep(Onboarding $onboarding): void;

    /**
     * Whether every step of the flow has been completed.
     */
    public function isComplete(Onboarding $onboarding): bool;
}
