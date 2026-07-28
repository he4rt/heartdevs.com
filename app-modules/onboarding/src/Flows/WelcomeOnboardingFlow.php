<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Flows;

use He4rt\Onboarding\Contracts\OnboardingFlow;
use He4rt\Onboarding\Contracts\OnboardingStepDTO;
use He4rt\Onboarding\DTOs\WelcomeFormDTO;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Exceptions\OnboardingPausedException;
use He4rt\Onboarding\Models\Onboarding;
use InvalidArgumentException;

final class WelcomeOnboardingFlow implements OnboardingFlow
{
    public function steps(): array
    {
        return ['form'];
    }

    public function stepDto(string $stepKey): OnboardingStepDTO
    {
        return match ($stepKey) {
            'form' => new WelcomeFormDTO(),
            default => throw new InvalidArgumentException('Step não suportado: '.$stepKey),
        };
    }

    public function prerequisites(): array
    {
        return [];
    }

    public function advance(Onboarding $onboarding): void
    {
        if ($onboarding->status === OnboardingStatus::Paused) {
            throw OnboardingPausedException::cannotAdvance($onboarding);
        }

        $advanced = $onboarding->steps()
            ->where('status', OnboardingStepStatus::Pending)->oldest()
            ->first()
            ?->update([
                'status' => OnboardingStepStatus::Done,
                'completed_at' => now(),
            ]);

        if ($advanced && $onboarding->status !== OnboardingStatus::Completed && $this->isComplete($onboarding)) {
            $onboarding->update([
                'status' => OnboardingStatus::Completed,
                'completed_at' => now(),
            ]);
        }
    }

    public function createNextStep(Onboarding $onboarding): void {}

    public function isComplete(Onboarding $onboarding): bool
    {
        return $onboarding->steps()
            ->where('status', OnboardingStepStatus::Done)
            ->count() === count($this->steps());
    }
}
