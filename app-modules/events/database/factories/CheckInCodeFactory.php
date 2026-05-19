<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/** @extends Factory<CheckInCode> */
final class CheckInCodeFactory extends Factory
{
    protected $model = CheckInCode::class;

    public function definition(): array
    {
        $startsAt = Date::now();

        return [
            'event_id' => Event::factory(),
            'event_date' => Date::today(),
            'code' => fake()->numerify('######'),
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->clone()->addHours(2),
            'max_uses' => null,
            'uses_count' => 0,
        ];
    }
}
