<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Actions;

use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class EnrollUserAction
{
    public function handle(Event $event, User $user): Enrollment
    {
        $this->validate($event, $user);

        return DB::transaction(function () use ($event, $user): Enrollment {
            throw_if(
                Enrollment::query()
                    ->where('event_id', $event->id)
                    ->where('user_id', $user->id)
                    ->exists(),
                EnrollmentException::alreadyEnrolled(),
            );

            $event->loadMissing('enrollmentPolicy');
            $policy = $event->enrollmentPolicy;

            $now = now();

            $enrollment = new Enrollment([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'enrolled_at' => $now,
                'confirmed_at' => $now,
            ]);
            $enrollment->status = EnrollmentStatus::Confirmed;
            $enrollment->save();

            EnrollmentTransition::query()->create([
                'enrollment_id' => $enrollment->id,
                'from_status' => null,
                'to_status' => EnrollmentStatus::Confirmed,
                'actor_id' => $user->id,
                'triggered_by' => TriggeredBy::User,
            ]);

            event(new EnrollmentConfirmed(
                enrollmentId: $enrollment->id,
                eventId: $event->id,
                userId: $user->id,
                xpRewardRsvp: $policy->xp_on_confirmed,
            ));

            return $enrollment->fresh(['event.enrollmentPolicy']);
        });
    }

    private function validate(Event $event, User $user): void
    {
        throw_unless(
            $event->status === EventStatus::Published,
            EnrollmentException::eventNotActive(),
        );

        throw_if(
            $event->starts_at->lte(now()),
            EnrollmentException::eventPast(),
        );

        $event->loadMissing('enrollmentPolicy');

        throw_unless(
            in_array(
                $event->enrollmentPolicy?->enrollment_method,
                [EnrollmentMethod::Rsvp, EnrollmentMethod::RsvpCheckin],
                strict: true,
            ),
            EnrollmentException::invalidEnrollmentMethod(),
        );

        throw_if(
            Enrollment::query()
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->exists(),
            EnrollmentException::alreadyEnrolled(),
        );
    }
}
