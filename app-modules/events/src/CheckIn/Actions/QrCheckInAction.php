<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Actions;

use He4rt\Events\CheckIn\DTOs\CheckInDTO;
use He4rt\Events\CheckIn\DTOs\QrCheckInDTO;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Enrollment\Enums\TriggeredBy;

final readonly class QrCheckInAction
{
    public function handle(QrCheckInDTO $dto): CheckIn
    {
        $qrToken = QrToken::query()
            ->with('enrollment')
            ->where('token', $dto->token)
            ->first();

        throw_unless(
            $qrToken !== null && $qrToken->enrollment->event_id === $dto->event->id,
            CheckInException::qrTokenNotFound(),
        );

        throw_if(
            $qrToken->expires_at !== null && $qrToken->expires_at->isPast(),
            CheckInException::qrTokenExpired(),
        );

        return resolve(CheckInAction::class)->handle(
            new CheckInDTO(
                enrollment: $qrToken->enrollment,
                method: CheckInMethod::QrCode,
                payload: ['token' => $dto->token],
                eventDate: $dto->eventDate,
                actorUserId: $dto->actorUserId,
                triggeredBy: TriggeredBy::Admin,
            ),
        );
    }
}
