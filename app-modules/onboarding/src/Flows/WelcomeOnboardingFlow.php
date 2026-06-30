<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Flows;

use He4rt\Onboarding\Contracts\OnboardingFlow;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Models\Onboarding;

final class WelcomeOnboardingFlow implements OnboardingFlow
{
    public function steps(): array
    {
        return ['form'];
    }

    public function prerequisites(): array
    {
        return [];
    }

    public function advance(Onboarding $onboarding): void
    {
        $onboarding->steps()
            ->where('status', OnboardingStepStatus::Pending)->oldest()
            ->first()
            ?->update([
                'status' => OnboardingStepStatus::Done,
                'completed_at' => now(),
            ]);

        if ($this->isComplete($onboarding)) {
            $onboarding->update([
                'status' => OnboardingStatus::Completed,
                'completed_at' => now(),
            ]);
        }
    }

    public function isComplete(Onboarding $onboarding): bool
    {
        return $onboarding->steps()
            ->where('status', OnboardingStepStatus::Done)
            ->count() === count($this->steps());
    }
}
