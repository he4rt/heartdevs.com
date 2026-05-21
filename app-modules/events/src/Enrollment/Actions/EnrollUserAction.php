<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Actions;

use He4rt\Events\Enrollment\DTOs\EnrollUserDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final readonly class EnrollUserAction
{
    public function handle(EnrollUserDTO $dto): Enrollment
    {
        $this->validate($dto);

        return DB::transaction(function () use ($dto): Enrollment {
            throw_if(
                Enrollment::query()
                    ->where('event_id', $dto->eventId)
                    ->where('user_id', $dto->userId)
                    ->exists(),
                EnrollmentException::alreadyEnrolled(),
            );

            try {
                $now = now();

                $enrollment = new Enrollment([
                    'event_id' => $dto->eventId,
                    'user_id' => $dto->userId,
                    'enrolled_at' => $now,
                    'confirmed_at' => $now,
                ]);
                $enrollment->status = EnrollmentStatus::Confirmed;
                $enrollment->save();

                EnrollmentTransition::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'from_status' => null,
                    'to_status' => EnrollmentStatus::Confirmed,
                    'actor_id' => $dto->userId,
                    'triggered_by' => TriggeredBy::User,
                ]);

                event(new EnrollmentConfirmed(
                    enrollmentId: $enrollment->id,
                    eventId: $dto->eventId,
                    userId: $dto->userId,
                    xpRewardRsvp: $dto->xpRewardOnConfirmed,
                ));

                return $enrollment->fresh(['event.enrollmentPolicy']);
            } catch (UniqueConstraintViolationException) {
                throw EnrollmentException::alreadyEnrolled();
            }
        });
    }

    private function validate(EnrollUserDTO $dto): void
    {
        throw_unless(
            $dto->eventStatus === EventStatus::Published,
            EnrollmentException::eventNotActive(),
        );

        throw_if(
            $dto->eventStartsAt->lte(now()),
            EnrollmentException::eventPast(),
        );

        throw_unless(
            in_array(
                $dto->enrollmentMethod,
                [EnrollmentMethod::Rsvp, EnrollmentMethod::RsvpCheckin],
                strict: true,
            ),
            EnrollmentException::invalidEnrollmentMethod(),
        );

        throw_if(
            Enrollment::query()
                ->where('event_id', $dto->eventId)
                ->where('user_id', $dto->userId)
                ->exists(),
            EnrollmentException::alreadyEnrolled(),
        );
    }
}
