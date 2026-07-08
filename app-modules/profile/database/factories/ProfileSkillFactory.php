<?php

declare(strict_types=1);

namespace He4rt\Profile\Database\Factories;

use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileSkill>
 */
final class ProfileSkillFactory extends Factory
{
    protected $model = ProfileSkill::class;

    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'skill_id' => Skill::factory(),
            'proficiency' => fake()->randomElement(SkillProficiency::cases()),
            'years_experience' => fake()->numberBetween(1, 20),
        ];
    }
}
