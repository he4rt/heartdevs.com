<?php

declare(strict_types=1);

namespace He4rt\Community\Database\Factories;

use He4rt\Community\UpcomingEvent\Enums\UpcomingEventCategory;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpcomingEvent>
 */
final class UpcomingEventFactory extends Factory
{
    protected $model = UpcomingEvent::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, asText: true),
            'description' => fake()->sentence(),
            'category' => UpcomingEventCategory::ReuniaoSemanal,
            'week_day' => fake()->numberBetween(0, 6),
            'time' => fake()->time('H:i'),
            'is_active' => true,
            'skip_next_occurrence' => false,
            'sort_order' => 0,
        ];
    }

    public function oneOff(): self
    {
        return $this->state(fn () => [
            'week_day' => null,
            'time' => null,
            'event_at' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
