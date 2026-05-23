<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Actions;

use He4rt\Events\Enrollment\DTOs\TransitionEnrollmentDTO;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;

final readonly class TransitionEnrollmentAction
{
    public function handle(TransitionEnrollmentDTO $dto): EnrollmentTransition
    {
        $fromStatus = $dto->enrollment->status;
        $toStatus = $dto->toStatus;

        throw_unless(
            $fromStatus->canTransitionTo($toStatus),
            EnrollmentException::invalidTransition($fromStatus, $toStatus),
        );

        $attributes = [
            'status' => $toStatus,
        ];

        if ($timestampColumn = $toStatus->timestampColumn()) {
            $attributes[$timestampColumn] = $dto->timestamp ?? now();
        }

        $dto->enrollment->update($attributes);

        return EnrollmentTransition::query()->create([
            'enrollment_id' => $dto->enrollment->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => $dto->actorId,
            'triggered_by' => $dto->triggeredBy,
            'reason' => $dto->reason,
            'metadata' => $dto->metadata ?: null,
        ]);
    }
}
