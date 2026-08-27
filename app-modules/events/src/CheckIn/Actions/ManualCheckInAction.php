<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Actions;

use He4rt\Events\CheckIn\DTOs\CheckInDTO;
use He4rt\Events\CheckIn\DTOs\ManualCheckInDTO;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Enums\TriggeredBy;

final readonly class ManualCheckInAction
{
    public function handle(ManualCheckInDTO $dto): CheckIn
    {
        return resolve(CheckInAction::class)->handle(
            new CheckInDTO(
                enrollment: $dto->enrollment,
                method: CheckInMethod::Manual,
                payload: ['actor_user_id' => $dto->actorUserId],
                eventDate: $dto->eventDate,
                actorUserId: $dto->actorUserId,
                triggeredBy: TriggeredBy::Admin,
            ),
        );
    }
}
