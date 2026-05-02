<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationAction> */
final class ModerationActionFactory extends Factory
{
    protected $model = ModerationAction::class;

    public function definition(): array
    {
        return [
            'case_id' => ModerationCase::factory(),
            'moderator_id' => User::factory(),
            'action_type' => fake()->randomElement(['warn', 'mute', 'ban']),
            'target_platforms' => ['discord'],
            'duration' => '7d',
            'reason' => fake()->sentence(),
            'automated' => false,
        ];
    }
}
