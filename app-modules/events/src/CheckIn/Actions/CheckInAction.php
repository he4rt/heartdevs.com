<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Actions;

use Carbon\CarbonInterface;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Events\ParticipantCheckedIn;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class CheckInAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        Enrollment $enrollment,
        CheckInMethod $method,
        array $payload,
        CarbonInterface $eventDate,
    ): CheckIn {
        return DB::transaction(function () use ($enrollment, $method, $payload, $eventDate): CheckIn {
            $enrollment = $this->loadLockedEnrollment($enrollment);
            $event = $enrollment->event;
            $normalizedEventDate = Date::parse($eventDate->toDateString())->startOfDay();
            $actorUserId = $payload['actor_user_id'] ?? null;

            $this->validate($enrollment, $method, $payload, $normalizedEventDate);
            $isFirstCheckIn = !CheckIn::query()
                ->where('enrollment_id', $enrollment->id)
                ->exists();

            try {
                $checkIn = CheckIn::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'method' => $method,
                    'payload' => $payload,
                    'event_date' => $normalizedEventDate,
                    'checked_in_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw EnrollmentException::alreadyCheckedInForDate();
            }

            if ($isFirstCheckIn && $enrollment->status === EnrollmentStatus::Confirmed) {
                $this->transitionToCheckedIn($enrollment, is_string($actorUserId) ? $actorUserId : null);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validate(Enrollment $enrollment, CheckInMethod $method, array $payload, CarbonInterface $eventDate): void
    {
        throw_if(
            $method === CheckInMethod::Manual && blank($payload['actor_user_id'] ?? null),
            EnrollmentException::invalidCheckInActor(),
        );

        throw_unless(
            in_array($enrollment->status, [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn], strict: true),
            EnrollmentException::invalidCheckInStatus(),
        );

        $event = $enrollment->event;
        $eventDateString = $eventDate->toDateString();

        throw_unless(
            $eventDateString >= $event->starts_at->toDateString() && $eventDateString <= $event->ends_at->toDateString(),
            EnrollmentException::checkInOutsideEventDateRange(),
        );

        throw_if(
            CheckIn::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('event_date', $eventDate->toDateString())
                ->exists(),
            EnrollmentException::alreadyCheckedInForDate(),
        );
    }

    private function transitionToCheckedIn(Enrollment $enrollment, ?string $actorUserId): void
    {
        $fromStatus = $enrollment->status;

        throw_unless(
            $fromStatus->canTransitionTo(EnrollmentStatus::CheckedIn),
            EnrollmentException::invalidCheckInStatus(),
        );

        $enrollment->forceFill([
            'status' => EnrollmentStatus::CheckedIn,
            'checked_in_at' => now(),
        ])->save();

        EnrollmentTransition::query()->create([
            'enrollment_id' => $enrollment->id,
            'from_status' => $fromStatus,
            'to_status' => EnrollmentStatus::CheckedIn,
            'actor_id' => $actorUserId,
            'triggered_by' => TriggeredBy::Admin,
        ]);
    }
}
