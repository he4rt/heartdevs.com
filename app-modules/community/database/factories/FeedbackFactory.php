<?php

declare(strict_types=1);

namespace He4rt\Community\Database\Factories;

use He4rt\Community\Feedback\Models\Feedback;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
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
