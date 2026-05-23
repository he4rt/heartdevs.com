<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Actions;

use Carbon\CarbonInterface;
use He4rt\Events\Enrollment\DTOs\EnrollUserDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final readonly class EnrollUserAction
{
    public function handle(EnrollUserDTO $dto): Enrollment
    {
        return DB::transaction(function () use ($dto): Enrollment {
            [$event, $policy] = $this->loadLockedEnrollmentContext($dto->eventId);

            $this->validate($event, $policy, $dto->userId);

            $initial = $this->resolveInitialEnrollment($dto->eventId, $policy);

            try {
                $enrollment = Enrollment::query()->create([
                    'event_id' => $dto->eventId,
                    'user_id' => $dto->userId,
                    'status' => $initial['status'],
                    'enrolled_at' => now(),
                    'confirmed_at' => $initial['confirmedAt'],
                    'waitlist_position' => $initial['waitlistPosition'],
                ]);

                EnrollmentTransition::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'from_status' => null,
                    'to_status' => $initial['status'],
                    'actor_id' => $dto->userId,
                    'triggered_by' => TriggeredBy::User,
                ]);

                if ($initial['status']->isConfirmed()) {
                    event(new EnrollmentConfirmed(
                        enrollmentId: $enrollment->id,
                        eventId: $dto->eventId,
                        userId: $dto->userId,
                        xpRewardOnConfirmed: $policy->xp_on_confirmed ?? 0,
                    ));
                }

                return $enrollment->fresh(['event.enrollmentPolicy']);
            } catch (UniqueConstraintViolationException) {
                throw EnrollmentException::alreadyEnrolled();
            }
        });
    }

    /**
     * Load event and policy for validation and capacity checks.
     *
     * lockForUpdate() runs SELECT ... FOR UPDATE. Row locks are held until this
     * DB::transaction commits or rolls back — there is no explicit unlock in code.
     * Returned models are reused below so rules run on fresh DB state, not caller snapshots.
     *
     * @return array{0: Event, 1: ?EnrollmentPolicy}
     */
    private function loadLockedEnrollmentContext(string $eventId): array
    {
        $event = Event::query()
            ->whereKey($eventId)
            ->lockForUpdate()
            ->firstOrFail();

        $policy = EnrollmentPolicy::query()
            ->where('event_id', $eventId)
            ->lockForUpdate()
            ->first();

        return [$event, $policy];
    }

    /**
     * @return array{status: EnrollmentStatus, waitlistPosition: ?int, confirmedAt: ?CarbonInterface}
     */
    private function resolveInitialEnrollment(string $eventId, ?EnrollmentPolicy $policy): array
    {
        $capacity = $policy?->capacity;

        if ($capacity === null) {
            return [
                'status' => EnrollmentStatus::Confirmed,
                'waitlistPosition' => null,
                'confirmedAt' => now(),
            ];
        }

        $confirmedCount = Enrollment::query()
            ->where('event_id', $eventId)
            ->confirmed()
            ->count();

        if ($confirmedCount < $capacity) {
            return [
                'status' => EnrollmentStatus::Confirmed,
                'waitlistPosition' => null,
                'confirmedAt' => now(),
            ];
        }

        if ($policy->has_waitlist) {
            $nextPosition = (int) Enrollment::query()
                ->where('event_id', $eventId)
                ->waitlisted()
                ->max('waitlist_position') + 1;

            return [
                'status' => EnrollmentStatus::Waitlisted,
                'waitlistPosition' => $nextPosition,
                'confirmedAt' => null,
            ];
        }

        throw EnrollmentException::eventFull();
    }

    private function validate(Event $event, ?EnrollmentPolicy $policy, string $userId): void
    {
        throw_unless(
            $event->status === EventStatus::Published,
            EnrollmentException::eventNotActive(),
        );

        throw_if(
            $event->starts_at->lte(now()),
            EnrollmentException::eventPast(),
        );

        throw_unless(
            in_array(
                $policy?->enrollment_method,
                [EnrollmentMethod::Rsvp, EnrollmentMethod::RsvpCheckin],
                strict: true,
            ),
            EnrollmentException::invalidEnrollmentMethod(),
        );

        throw_if(
            Enrollment::query()
                ->where('event_id', $event->id)
                ->where('user_id', $userId)
                ->exists(),
            EnrollmentException::alreadyEnrolled(),
        );
    }
}
