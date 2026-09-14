<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Actions;

use He4rt\Events\Enrollment\DTOs\ApproveApplicationDTO;
use He4rt\Events\Enrollment\DTOs\TransitionEnrollmentDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Events\EnrollmentWaitlisted;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use Illuminate\Support\Facades\DB;

final readonly class ApproveApplicationAction
{
    public function __construct(
        private TransitionEnrollmentAction $transitionEnrollment,
    ) {}

    public function handle(ApproveApplicationDTO $dto): Enrollment
    {
        return DB::transaction(function () use ($dto): Enrollment {
            $enrollment = Enrollment::query()
                ->whereKey($dto->enrollmentId)
                ->lockForUpdate()
                ->firstOrFail();

            throw_unless(
                $enrollment->status === EnrollmentStatus::Pending,
                EnrollmentException::enrollmentNotPending(),
            );

            $policy = EnrollmentPolicy::query()
                ->where('event_id', $enrollment->event_id)
                ->lockForUpdate()
                ->firstOrFail();

            $toStatus = $this->resolveApprovalStatus($enrollment->event_id, $policy);

            $waitlistPosition = null;
            if ($toStatus === EnrollmentStatus::Waitlisted) {
                $waitlistPosition = (int) Enrollment::query()
                    ->where('event_id', $enrollment->event_id)
                    ->waitlisted()
                    ->max('waitlist_position') + 1;

                $enrollment->update(['waitlist_position' => $waitlistPosition]);
            }

            $this->transitionEnrollment->handle(new TransitionEnrollmentDTO(
                enrollment: $enrollment,
                toStatus: $toStatus,
                triggeredBy: TriggeredBy::Admin,
                actorId: $dto->actorId,
            ));

            if ($toStatus->isConfirmed()) {
                event(new EnrollmentConfirmed(
                    enrollmentId: $enrollment->id,
                    eventId: $enrollment->event_id,
                    userId: $enrollment->user_id,
                    xpRewardOnConfirmed: $policy->xp_on_confirmed ?? 0,
                ));
            }

            if ($toStatus->isWaitlisted()) {
                event(new EnrollmentWaitlisted(
                    enrollmentId: $enrollment->id,
                    eventId: $enrollment->event_id,
                    userId: $enrollment->user_id,
                    waitlistPosition: $waitlistPosition,
                ));
            }

            return $enrollment->fresh(['event.enrollmentPolicy']);
        });
    }

    private function resolveApprovalStatus(string $eventId, EnrollmentPolicy $policy): EnrollmentStatus
    {
        $capacity = $policy->capacity;

        if ($capacity === null) {
            return EnrollmentStatus::Confirmed;
        }

        $occupiedCount = Enrollment::query()
            ->where('event_id', $eventId)
            ->active()
            ->count();

        if ($occupiedCount < $capacity) {
            return EnrollmentStatus::Confirmed;
        }

        if ($policy->has_waitlist) {
            return EnrollmentStatus::Waitlisted;
        }

        throw EnrollmentException::eventFull();
    }
}
