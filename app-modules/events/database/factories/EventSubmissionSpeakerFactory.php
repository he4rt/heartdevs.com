<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\Pivot\EventSubmissionSpeaker;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventSubmissionSpeaker> */
class EventSubmissionSpeakerFactory extends Factory
{
    protected $model = EventSubmissionSpeaker::class;

    public function definition(): array
    {
        return [
            'event_id' => EventModel::factory(),
            'user_id' => User::factory(),
        ];
    }
}
