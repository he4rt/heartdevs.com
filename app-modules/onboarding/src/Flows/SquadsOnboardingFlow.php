<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Flows;

use He4rt\Onboarding\Contracts\OnboardingFlow;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Models\Onboarding;

final class SquadsOnboardingFlow implements OnboardingFlow
{
    public function steps(): array
    {
        return ['form', 'git_challenge'];
    }

    public function prerequisites(): array
    {
        // TO-DO: #351
        return [];
    }

    public function advance(Onboarding $onboarding): void
    {
        $nextStep = $onboarding->steps()
            ->where('status', OnboardingStepStatus::Pending)
            ->oldest()
            ->first();

        if (!$nextStep) {
            return;
        }

        $nextStep->update([
            'status' => OnboardingStepStatus::Done,
            'completed_at' => now(),
        ]);

        if ($onboarding->status !== OnboardingStatus::Completed && $this->isComplete($onboarding)) {
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
