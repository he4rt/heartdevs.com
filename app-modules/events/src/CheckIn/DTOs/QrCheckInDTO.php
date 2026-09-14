<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\DTOs;

use Carbon\CarbonInterface;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\Event\Models\Event;

final readonly class QrCheckInDTO
{
    public function __construct(
        public string $token,
        public Event $event,
        public CarbonInterface $eventDate,
        public string $actorUserId,
    ) {
        throw_if(blank($this->token), CheckInException::qrTokenNotFound());
        throw_if(blank($this->actorUserId), CheckInException::invalidCheckInActor());
    }
}
