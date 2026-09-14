<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Actions;

use Carbon\CarbonInterface;
use He4rt\Events\CheckIn\DTOs\CheckInDTO;
use He4rt\Events\CheckIn\Events\ParticipantCheckedIn;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Actions\TransitionEnrollmentAction;
use He4rt\Events\Enrollment\DTOs\TransitionEnrollmentDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class CheckInAction
{
    public function handle(CheckInDTO $dto): CheckIn
    {
        return DB::transaction(function () use ($dto): CheckIn {
            $enrollment = $this->loadLockedEnrollment($dto->enrollment);
            $event = $enrollment->event;
            $normalizedEventDate = Date::parse($dto->eventDate->toDateString())->startOfDay();

            $this->validateCommonRules($enrollment, $normalizedEventDate);
            $isFirstCheckIn = !CheckIn::query()
                ->where('enrollment_id', $enrollment->id)
                ->exists();

            try {
                $checkIn = CheckIn::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'method' => $dto->method,
                    'payload' => $dto->payload,
                    'event_date' => $normalizedEventDate,
                    'checked_in_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw CheckInException::alreadyCheckedInForDate();
            }

            if ($isFirstCheckIn && $enrollment->status === EnrollmentStatus::Confirmed) {
                resolve(TransitionEnrollmentAction::class)->handle(
                    new TransitionEnrollmentDTO(
                        enrollment: $enrollment,
                        toStatus: EnrollmentStatus::CheckedIn,
                        triggeredBy: $dto->triggeredBy,
                        actorId: $dto->actorUserId,
                        timestamp: $checkIn->checked_in_at,
                    ),
                );
            }

            $xpRewardOnCheckedIn = (int) ($event->enrollmentPolicy->xp_on_checked_in ?? 0);

            event(new ParticipantCheckedIn(
                checkInId: $checkIn->id,
                enrollmentId: $enrollment->id,
                eventId: $enrollment->event_id,
                userId: $enrollment->user_id,
                eventDate: $normalizedEventDate->toDateString(),
                xpRewardOnCheckedIn: $xpRewardOnCheckedIn,
            ));

            return $checkIn->load('enrollment');
        });
    }

    private function loadLockedEnrollment(Enrollment $enrollment): Enrollment
    {
        return Enrollment::query()
            ->with(['event.enrollmentPolicy'])
            ->whereKey($enrollment->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function validateCommonRules(Enrollment $enrollment, CarbonInterface $eventDate): void
    {
        throw_unless(
            in_array($enrollment->status, [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn], strict: true),
            CheckInException::invalidCheckInStatus(),
        );

        $event = $enrollment->event;
        $eventDateString = $eventDate->toDateString();

        throw_unless(
            $eventDateString >= $event->starts_at->toDateString() && $eventDateString <= $event->ends_at->toDateString(),
            CheckInException::checkInOutsideEventDateRange(),
        );

        throw_if(
            CheckIn::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('event_date', $eventDate->toDateString())
                ->exists(),
            CheckInException::alreadyCheckedInForDate(),
        );
    }
}
