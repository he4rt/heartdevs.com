<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Actions;

use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Exceptions\InvalidOnboardingStatusTransition;
use He4rt\Onboarding\Models\Onboarding;

final class PauseOnboarding
{
    public function handle(Onboarding $onboarding): Onboarding
    {
        if (!$onboarding->status->canTransitionTo(OnboardingStatus::Paused)) {
            throw InvalidOnboardingStatusTransition::between($onboarding->status, OnboardingStatus::Paused);
        }

        $onboarding->update([
            'status' => OnboardingStatus::Paused,
            'paused_at' => now(),
        ]);

        return $onboarding->refresh();
    }
}
