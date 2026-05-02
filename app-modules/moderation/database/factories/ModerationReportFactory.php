<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationCase;
use He4rt\Moderation\Models\ModerationReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationReport> */
final class ModerationReportFactory extends Factory
{
    protected $model = ModerationReport::class;

    public function definition(): array
    {
        return [
            'case_id' => ModerationCase::factory(),
            'reporter_id' => User::factory(),
            'reason' => fake()->randomElement(['spam', 'toxicity', 'harassment']),
            'details' => fake()->sentence(),
            'platform' => 'discord',
        ];
    }
}
