<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Actions;

use He4rt\Events\CheckIn\DTOs\CheckInDTO;
use He4rt\Events\CheckIn\DTOs\NumericCodeCheckInDTO;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use Illuminate\Support\Facades\DB;

final readonly class NumericCodeCheckInAction
{
    public function handle(NumericCodeCheckInDTO $dto): CheckIn
    {
        return DB::transaction(function () use ($dto): CheckIn {
            $codeRecord = CheckInCode::query()
                ->where('code', $dto->code)
                ->where('event_id', $dto->enrollment->event_id)
                ->lockForUpdate()
                ->first();

            if ($codeRecord === null) {
                throw CheckInException::invalidCheckInCode();
            }

            if (!$codeRecord->event_date->isSameDay($dto->eventDate)) {
                throw CheckInException::checkInCodeWrongDate();
            }

            if ($codeRecord->expires_at->isPast() || $codeRecord->revoked_at !== null) {
                throw CheckInException::checkInCodeExpired();
            }

            if ($codeRecord->max_uses !== null && $codeRecord->uses_count >= $codeRecord->max_uses) {
                throw CheckInException::checkInCodeExhausted();
            }

            $codeRecord->increment('uses_count');

            return resolve(CheckInAction::class)->handle(
                new CheckInDTO(
                    enrollment: $dto->enrollment,
                    method: CheckInMethod::NumericCode,
                    payload: ['code' => $dto->code],
                    eventDate: $dto->eventDate,
                    actorUserId: $dto->enrollment->user_id,
                    triggeredBy: TriggeredBy::User,
                ),
            );
        });
    }
}
