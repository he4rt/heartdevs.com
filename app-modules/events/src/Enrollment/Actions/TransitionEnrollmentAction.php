<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Actions;

use Carbon\CarbonInterface;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;

final readonly class TransitionEnrollmentAction
{
    private const array TIMESTAMP_MAP = [
        'confirmed' => 'confirmed_at',
        'checked_in' => 'checked_in_at',
        'attended' => 'attended_at',
        'cancelled' => 'cancelled_at',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        Enrollment $enrollment,
        EnrollmentStatus $toStatus,
        TriggeredBy $triggeredBy,
        ?string $actorId = null,
        ?string $reason = null,
        array $metadata = [],
        ?CarbonInterface $timestamp = null,
    ): EnrollmentTransition {
        $fromStatus = $enrollment->status;

        throw_unless(
            $fromStatus->canTransitionTo($toStatus),
            EnrollmentException::invalidTransition($fromStatus, $toStatus),
        );

        $timestampColumn = $this->resolveTimestampColumn($toStatus);

        $enrollment->forceFill([
            'status' => $toStatus,
            $timestampColumn => $timestamp ?? now(),
        ])->save();

        return EnrollmentTransition::query()->create([
            'enrollment_id' => $enrollment->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => $actorId,
            'triggered_by' => $triggeredBy,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function resolveTimestampColumn(EnrollmentStatus $status): ?string
    {
        return self::TIMESTAMP_MAP[$status->value] ?? null;
    }
}
