<?php

declare(strict_types=1);

namespace He4rt\Profile\Database\Factories;

use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkExperience>
 */
final class WorkExperienceFactory extends Factory
{
    protected $model = WorkExperience::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 years', '-1 year');
        $isCurrent = fake()->boolean(30);

        return [
            'profile_id' => Profile::factory(),
            'company_name' => fake()->company(),
            'position' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'start_date' => $start,
            'end_date' => $isCurrent ? null : fake()->dateTimeBetween($start, 'now'),
            'is_currently_working_here' => $isCurrent,
        ];
    }

    public function current(): self
    {
        return $this->state(fn (): array => [
            'is_currently_working_here' => true,
            'end_date' => null,
        ]);
    }
}
