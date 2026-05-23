<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Actions;

use Carbon\CarbonInterface;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;

final readonly class ManualCheckInAction
{
    public function handle(
        Enrollment $enrollment,
        string $actorUserId,
        CarbonInterface $eventDate,
    ): CheckIn {
        throw_if(
            blank($actorUserId),
            CheckInException::invalidCheckInActor(),
        );

        return resolve(CheckInService::class)->handle(
            enrollment: $enrollment,
            method: CheckInMethod::Manual,
            payload: ['actor_user_id' => $actorUserId],
            eventDate: $eventDate,
            actorUserId: $actorUserId,
            triggeredBy: TriggeredBy::Admin,
        );
    }
}
