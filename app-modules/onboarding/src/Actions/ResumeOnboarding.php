<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Actions;

use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Exceptions\InvalidOnboardingStatusTransition;
use He4rt\Onboarding\Models\Onboarding;

final class ResumeOnboarding
{
    public function handle(Onboarding $onboarding): Onboarding
    {
        if (!$onboarding->status->canTransitionTo(OnboardingStatus::InProgress)) {
            throw InvalidOnboardingStatusTransition::between($onboarding->status, OnboardingStatus::InProgress);
        }

        $onboarding->update([
            'status' => OnboardingStatus::InProgress,
            'paused_at' => null,
        ]);

        return $onboarding->refresh();
    }
}
