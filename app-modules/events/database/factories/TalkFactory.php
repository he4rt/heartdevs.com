<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\EventSubmission;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<EventSubmission>
 */
final class TalkFactory extends Factory
{
    protected $model = EventSubmission::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'event_id' => EventModel::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(TalkStatusEnum::cases()),
            'field_type' => fake()->word(),
            'title' => fake()->word(),
            'description' => fake()->text(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
