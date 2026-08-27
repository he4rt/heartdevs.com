<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Timeline> */
final class TimelineFactory extends Factory
{
    protected $model = Timeline::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => PostEntry::factory(),
            'is_ignored' => false,
            'pinned' => false,
            'views' => 0,
        ];
    }

    public function pinned(): static
    {
        return $this->state(['pinned' => true]);
    }

    public function ignored(): static
    {
        return $this->state(['is_ignored' => true]);
    }
}
