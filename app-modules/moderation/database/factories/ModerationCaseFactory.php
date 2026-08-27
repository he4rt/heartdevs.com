<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationCase> */
final class ModerationCaseFactory extends Factory
{
    protected $model = ModerationCase::class;

    public function definition(): array
    {
        return [
            'content_type' => 'message',
            'content_id' => fake()->uuid(),
            'content_snapshot' => ['text' => fake()->sentence()],
            'source_platform' => 'discord',
            'source' => 'user_report',
            'status' => 'pending',
            'priority' => fake()->numberBetween(0, 100),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'violation_type' => fake()->randomElement(['spam', 'toxicity', 'harassment']),
            'ai_scores' => ['spam' => fake()->randomFloat(2, 0, 1)],
            'author_id' => User::factory(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => 95, 'severity' => 'critical']);
    }
}
