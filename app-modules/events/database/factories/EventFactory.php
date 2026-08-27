<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
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

    public function asWorkshop(): static
    {
        return $this->state(fn (): array => [
            'event_type' => EventType::Workshop,
            'status' => EventStatus::Published,
        ])->afterCreating(static function (Event $event): void {
            EnrollmentPolicy::factory()->create([
                'event_id' => $event->id,
                'enrollment_method' => EnrollmentMethod::RsvpCheckin,
                'check_in_method' => CheckInMethod::NumericCode,
                'capacity' => 50,
                'has_waitlist' => true,
                'attendance_requirement' => AttendanceRequirement::AllDays,
                'xp_on_confirmed' => 100,
                'xp_on_checked_in' => 200,
                'xp_on_attended' => 500,
            ]);
        });
    }

    public function asMeetup(): static
    {
        return $this->state(fn (): array => [
            'event_type' => EventType::Meetup,
            'status' => EventStatus::Published,
        ])->afterCreating(static function (Event $event): void {
            EnrollmentPolicy::factory()->create([
                'event_id' => $event->id,
                'enrollment_method' => EnrollmentMethod::Rsvp,
                'check_in_method' => CheckInMethod::Manual,
                'capacity' => null,
                'has_waitlist' => false,
                'attendance_requirement' => AttendanceRequirement::AllDays,
                'xp_on_confirmed' => 50,
                'xp_on_checked_in' => 100,
                'xp_on_attended' => 250,
            ]);
        });
    }

    public function asConference(): static
    {
        return $this->state(fn (): array => [
            'event_type' => EventType::Conference,
            'status' => EventStatus::Published,
        ])->afterCreating(static function (Event $event): void {
            EnrollmentPolicy::factory()->create([
                'event_id' => $event->id,
                'enrollment_method' => EnrollmentMethod::Application,
                'check_in_method' => CheckInMethod::QrCode,
                'capacity' => 200,
                'has_waitlist' => true,
                'attendance_requirement' => AttendanceRequirement::MinimumDays,
                'minimum_days' => 2,
                'cancellation_deadline_hours' => 48,
                'xp_on_confirmed' => 200,
                'xp_on_checked_in' => 300,
                'xp_on_attended' => 1_000,
            ]);
        });
    }
}
