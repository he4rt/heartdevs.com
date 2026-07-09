<?php

declare(strict_types=1);

namespace He4rt\Profile\Database\Factories;

use He4rt\Profile\Enums\SkillCategory;
use He4rt\Profile\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Skill>
 */
final class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'category' => fake()->randomElement(SkillCategory::cases()),
            'icon' => null,
        ];
    }
}
