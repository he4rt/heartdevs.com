<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Actions;

use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Models\Onboarding;

final class AdvanceStep
{
    public function handle(Onboarding $onboarding, array $payload): void
    {
        $step = $onboarding->steps()
            ->where('status', OnboardingStepStatus::Pending)
            ->oldest()
            ->firstOrFail();

        $flow = $onboarding->type->handler();

        $dtoClass = $flow->stepDto($step->step_key);

        $dto = $dtoClass::validate($payload);

        $step->update([
            'data' => $dto->toArray(),
        ]);

        $flow->advance($onboarding);
    }
}
