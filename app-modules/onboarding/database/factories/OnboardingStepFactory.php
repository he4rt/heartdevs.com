<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Database\Factories;

use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Models\Onboarding;
use He4rt\Onboarding\Models\OnboardingStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OnboardingStep> */
final class OnboardingStepFactory extends Factory
{
    protected $model = OnboardingStep::class;

    public function definition(): array
    {
        return [
            'onboarding_id' => Onboarding::factory(),
            'step_key' => 'form',
            'status' => OnboardingStepStatus::Pending,
            'data' => null,
        ];
    }
}
