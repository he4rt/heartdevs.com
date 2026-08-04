<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Flows;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Onboarding\Contracts\OnboardingFlow;
use He4rt\Onboarding\Contracts\OnboardingStepDTO;
use He4rt\Onboarding\DTOs\WelcomeFormDTO;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Exceptions\GateBlockedException;
use He4rt\Onboarding\Models\Onboarding;
use InvalidArgumentException;

final class SquadsOnboardingFlow implements OnboardingFlow
{
    public function steps(): array
    {
        return ['form', 'git_challenge'];
    }

    public function stepDto(string $stepKey): OnboardingStepDTO
    {
        return match ($stepKey) {
            'form' => new WelcomeFormDTO(),
            default => throw new InvalidArgumentException('Step não suportado: '.$stepKey),
        };
    }

    /**
     * @return list<OnboardingType>
     */
    public function prerequisites(): array
    {
        return [OnboardingType::Welcome];
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

    public function createNextStep(Onboarding $onboarding): void
    {
        $doneKeys = $onboarding->steps()
            ->where('status', OnboardingStepStatus::Done)
            ->pluck('step_key')
            ->toArray();

        $remaining = array_values(array_diff($this->steps(), $doneKeys));

        if ($remaining === []) {
            return;
        }

        $nextKey = $remaining[0];

        if ($nextKey === 'git_challenge') {
            $linked = $onboarding->user->providers()
                ->where('provider', IdentityProvider::GitHub)
                ->whereNotNull('connected_at')
                ->whereNull('disconnected_at')
                ->exists();
            throw_unless($linked, GateBlockedException::class, step: 'git_challenge', reason: 'github_not_linked');
        }

        $onboarding->steps()->firstOrCreate(
            ['step_key' => $nextKey],
            ['status' => OnboardingStepStatus::Pending],
        );
    }

    public function isComplete(Onboarding $onboarding): bool
    {
        return $onboarding->steps()
            ->where('status', OnboardingStepStatus::Done)
            ->count() === count($this->steps());
    }
}
