<?php

declare(strict_types=1);

namespace He4rt\Feedback\Database\Factories;

use He4rt\Feedback\Enum\ReviewTypeEnum;
use He4rt\Feedback\Models\Feedback;
use He4rt\Feedback\Models\Review;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
final class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'feedback_id' => Feedback::factory(),
            'staff_id' => User::factory(),
            'status' => fake()->randomElement(ReviewTypeEnum::cases()),
            'reason' => fake()->sentence(),
            'received_at' => now(),
        ];
    }
}
