<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\DTOs;

use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;

final readonly class EnrollUserDTO
{
    public function __construct(
        public string $eventId,
        public string $userId,
    ) {}

    public static function fromModels(Event $event, User $user): self
    {
        return new self(
            eventId: $event->id,
            userId: $user->id,
        );
    }
}
