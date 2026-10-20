<?php

declare(strict_types=1);

namespace He4rt\Delas\Database\Factories;

use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Enums\DelasTriggeredBy;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DelasTransition> */
final class DelasTransitionFactory extends Factory
{
    protected $model = DelasTransition::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'request_id' => null,
            'block_id' => null,
            'action' => DelasAction::Requested,
            'from_status' => null,
            'to_status' => DelasRequestStatus::Pending,
            'actor_id' => null,
            'triggered_by' => DelasTriggeredBy::User,
            'reason' => null,
        ];
    }
}
