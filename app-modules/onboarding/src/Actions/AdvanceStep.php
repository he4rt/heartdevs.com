<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Actions;

use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Models\Onboarding;

final class AdvanceStep
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Onboarding $onboarding, array $payload): void
    {
        $step = $onboarding->steps()
            ->where('status', OnboardingStepStatus::Pending)
            ->oldest()
            ->firstOrFail();

        $flow = $onboarding->type->handler();

        $dto = $flow
            ->stepDto($step->step_key)
            ->validate($payload);

        $step->update([
            'data' => $dto->toArray(),
        ]);

        $flow->advance($onboarding);

        if (!$flow->isComplete($onboarding)) {
            $flow->createNextStep($onboarding);
        }
    }
}
