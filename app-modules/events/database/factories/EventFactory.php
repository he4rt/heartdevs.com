<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Event\Enums\EventStatus;
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
            'status' => EventStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => EventStatus::Published,
        ]);
    }

    public function upcoming(): static
    {
        $startsAt = Date::now()->addDays(7);

        return $this->state(fn (): array => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addHours(3),
        ]);
    }

    public function past(): static
    {
        $startsAt = Date::now()->subDays(7);

        return $this->state(fn (): array => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addHours(3),
        ]);
    }
}
