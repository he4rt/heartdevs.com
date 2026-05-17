<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/** @extends Factory<Event> */
final class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startsAt = Date::now()->addDays(fake()->numberBetween(1, 30));

        return [
            'tenant_id' => Tenant::factory(),
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->text(),
            'event_type' => fake()->randomElement(EventType::cases()),
            'location' => fake()->optional()->address(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addHours(fake()->numberBetween(2, 8)),
            'is_published' => false,
        ];
    }
}
