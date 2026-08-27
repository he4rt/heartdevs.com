<?php

declare(strict_types=1);

namespace He4rt\Events\Closure\Actions;

use He4rt\Events\Closure\Events\ParticipantAttended;
use He4rt\Events\Enrollment\Actions\TransitionEnrollmentAction;
use He4rt\Events\Enrollment\DTOs\TransitionEnrollmentDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CloseEventAction
{
    public function handle(Event $event): int
    {
        $attendances = 0;

        $event->enrollments()
            ->whereIn('status', [EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn])
            ->eachById(function (Enrollment $enrollment) use (&$attendances): void {
                $attendances += $this->closeOne($enrollment);
            });

        return $attendances;
    }

    /**
     * @throws Throwable
     */
    private function closeOne(Enrollment $enrollment): int
    {
        return DB::transaction(static function () use ($enrollment): int {
            $locked = Enrollment::query()
                ->with('event.enrollmentPolicy')
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->status->isTerminal()) {
                return 0;
            }

            $toStatus = $locked->status === EnrollmentStatus::CheckedIn
                ? $locked->event->enrollmentPolicy->resolveAttendance($locked)
                : EnrollmentStatus::NoShow;

            resolve(TransitionEnrollmentAction::class)->handle(
                new TransitionEnrollmentDTO(
                    enrollment: $locked,
                    toStatus: $toStatus,
                    triggeredBy: TriggeredBy::System,
                ),
            );

            if ($toStatus === EnrollmentStatus::Attended) {
                $xpReward = ($locked->event->enrollmentPolicy->xp_on_attended ?? 0);

                event(new ParticipantAttended(
                    enrollmentId: $locked->id,
                    eventId: $locked->event_id,
                    userId: $locked->user_id,
                    xpRewardOnAttended: $xpReward,
                ));

                return 1;
            }

            return 0;
        });
    }
}
