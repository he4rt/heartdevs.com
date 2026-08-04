<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Onboarding> */
final class OnboardingFactory extends Factory
{
    protected $model = Onboarding::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => OnboardingType::Welcome,
            'status' => OnboardingStatus::InProgress,
            'completed_at' => null,
            'paused_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => OnboardingStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
