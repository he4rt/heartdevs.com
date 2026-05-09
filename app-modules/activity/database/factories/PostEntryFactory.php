<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PostEntry> */
final class PostEntryFactory extends Factory
{
    protected $model = PostEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'content' => fake()->sentences(3, true),
        ];
    }
}
