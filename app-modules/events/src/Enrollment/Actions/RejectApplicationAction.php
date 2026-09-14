<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Actions;

use He4rt\Events\Enrollment\DTOs\RejectApplicationDTO;
use He4rt\Events\Enrollment\DTOs\TransitionEnrollmentDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use Illuminate\Support\Facades\DB;

final readonly class RejectApplicationAction
{
    public function __construct(
        private TransitionEnrollmentAction $transitionEnrollment,
    ) {}

    public function handle(RejectApplicationDTO $dto): Enrollment
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

            $enrollment->update(['rejection_reason' => $dto->reason]);

            $this->transitionEnrollment->handle(new TransitionEnrollmentDTO(
                enrollment: $enrollment,
                toStatus: EnrollmentStatus::Rejected,
                triggeredBy: TriggeredBy::Admin,
                actorId: $dto->actorId,
                reason: $dto->reason,
            ));

            return $enrollment->fresh();
        });
    }
}
