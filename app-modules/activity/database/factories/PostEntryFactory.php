<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<PostEntry>
 */
class PostEntryFactory extends Factory
{
    protected $model = PostEntry::class;

    public function definition(): array
    {
        return [
            'content' => fake()->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
