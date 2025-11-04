<?php

declare(strict_types=1);

namespace factories;

use Illuminate\Support\Facades\Date;
use He4rt\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'event_type' => fake()->word(),
            'slug' => fake()->slug(),
            'active' => true,
            'title' => fake()->word(),
            'description' => fake()->text(),
            'event_at' => Date::now(),
            'start_at' => Date::now(),
            'end_at' => Date::now(),
            'location' => fake()->word(),
            'max_attendees' => fake()->randomNumber(),
            'attendees_count' => 0,
            'waitlist_count' => 0,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
