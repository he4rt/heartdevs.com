<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\DTOs;

use Carbon\CarbonInterface;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;

final readonly class EnrollUserDTO
{
    public function __construct(
        public string $eventId,
        public string $userId
    ) {}

    public static function fromModels(Event $event, User $user): self
    {
        $event->loadMissing('enrollmentPolicy');

        return new self(
            eventId: $event->id,
            userId: $user->id,
            eventStatus: $event->status,
            eventStartsAt: $event->starts_at,
            enrollmentMethod: $event->enrollmentPolicy?->enrollment_method,
            capacity: $event->enrollmentPolicy?->capacity,
            hasWaitlist: $event->enrollmentPolicy?->has_waitlist ?? false,
            xpRewardOnConfirmed: $event->enrollmentPolicy?->xp_on_confirmed ?? 0,
        );
    }
}
