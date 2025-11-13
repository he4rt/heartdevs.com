<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<EventModel>
 */
final class EventFactory extends Factory
{
    protected $model = EventModel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'event_type' => fake()->randomElement(EventTypeEnum::cases()),
            'slug' => fake()->slug(),
            'active' => true,
            'title' => fake()->sentence(4),
            'description' => fake()->text(),
            'event_at' => Date::now(),
            'start_at' => Date::now(),
            'end_at' => Date::now(),
            'location' => fake()->sentence(3),
            'max_attendees' => fake()->numberBetween(10, 100),
            'attendees_count' => 0,
            'waitlist_count' => 0,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
