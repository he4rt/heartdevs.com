<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Database\Factories;

use He4rt\Identity\Tenant\Models\Tenant;
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
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'type' => OnboardingType::Welcome,
            'status' => OnboardingStatus::InProgress,
            'completed_at' => null,
            'paused_at' => null,
        ];
    }
}
