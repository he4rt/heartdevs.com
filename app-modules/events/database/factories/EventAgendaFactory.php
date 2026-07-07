<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Models\EventAgenda;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\EventSegment;
use He4rt\Events\Models\EventSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<EventAgenda>
 */
final class EventAgendaFactory extends Factory
{
    protected $model = EventAgenda::class;

    public function definition(): array
    {
        return [
            'event_id' => EventModel::factory(),
            'start_at' => Date::now(),
            'end_at' => Date::now()->addHour(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function forSegment(?EventSegment $segment = null)
    {
        return $this->state(fn () => [
            'schedulable_type' => (new EventSegment)->getMorphClass(),
            'schedulable_id' => $segment ?? EventSegment::query()->inRandomOrder()->first()->getKey(),
        ]);
    }

    public function forTalk(?EventSubmission $talk = null)
    {
        return $this->state(fn () => [
            'schedulable_type' => (new EventSubmission)->getMorphClass(),
            'schedulable_id' => $talk ?? EventSubmission::factory(),
        ]);
    }
}
