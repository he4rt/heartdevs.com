<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationAppeal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationAppeal> */
final class ModerationAppealFactory extends Factory
{
    protected $model = ModerationAppeal::class;

    public function definition(): array
    {
        return [
            'action_id' => ModerationAction::factory(),
            'appellant_id' => User::factory(),
            'reason_category' => 'context_misunderstood',
            'reason_text' => fake()->sentence(),
            'status' => 'pending',
            'sla_deadline' => now()->addHours(48),
        ];
    }
}
