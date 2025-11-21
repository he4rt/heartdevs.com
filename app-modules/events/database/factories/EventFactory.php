<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
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
            'event_at' => Date::today(),
            'start_at' => Date::now(),
            'end_at' => Date::today()->endOfDay(),
            'location' => fake()->sentence(3),
            'max_attendees' => fake()->numberBetween(10, 100),
            'attendees_count' => 0,
            'waitlist_count' => 0,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function withAttendees(?int $count = null, AttendingStatusEnum $status = AttendingStatusEnum::Attending): self
    {
        return $this->afterCreating(function (EventModel $model) use ($count, $status): void {
            $attendeesCount = $count ?? fake()->numberBetween(3, 10);
            $attendees = User::factory()->count($attendeesCount)->create();

            foreach ($attendees as $user) {
                $model->attendees()->attach($user->getKey(), [
                    'status' => $status,
                ]);
            }
        });
    }

    public function withMixedAttendees(): self
    {
        return $this->afterCreating(function (EventModel $model): void {
            $attending = User::factory()->count(fake()->numberBetween(5, 8))->create();
            foreach ($attending as $user) {
                $model->attendees()->attach($user->getKey(), [
                    'status' => AttendingStatusEnum::Attending,
                ]);
            }

            $waitlist = User::factory()->count(fake()->numberBetween(2, 5))->create();
            foreach ($waitlist as $user) {
                $model->attendees()->attach($user->getKey(), [
                    'status' => AttendingStatusEnum::Waitlist,
                ]);
            }
        });
    }
}
