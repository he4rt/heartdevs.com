<?php

declare(strict_types=1);

namespace He4rt\Feedback\Database\Factories;

use He4rt\Feedback\Models\Feedback;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'sender_id' => User::factory(),
            'target_id' => User::factory(),
            'type' => fake()->randomElement(['compliment', 'improvement']),
            'message' => fake()->sentence(),

        ];
    }
}
