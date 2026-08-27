<?php

declare(strict_types=1);

namespace He4rt\Events\Closure\Actions;

use He4rt\Events\Closure\DTOs\OverrideEnrollmentStatusDTO;
use He4rt\Events\Closure\Exceptions\OverrideEnrollmentStatusException;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class OverrideEnrollmentStatusAction
{
    /** @var list<array{from: EnrollmentStatus, to: EnrollmentStatus}> */
    private const array ALLOWED_OVERRIDES = [
        ['from' => EnrollmentStatus::NoShow, 'to' => EnrollmentStatus::Attended],
        ['from' => EnrollmentStatus::Confirmed, 'to' => EnrollmentStatus::CheckedIn],
    ];

    /** @return list<EnrollmentStatus> */
    public static function allowedTargetsFor(EnrollmentStatus $from): array
    {
        $targets = [];

        foreach (self::ALLOWED_OVERRIDES as $pair) {
            if ($pair['from'] === $from) {
                $targets[] = $pair['to'];
            }
        }

        return $targets;
    }

    /**
     * @throws OverrideEnrollmentStatusException
     * @throws Throwable
     */
    public function handle(OverrideEnrollmentStatusDTO $dto): EnrollmentTransition
    {
        return DB::transaction(function () use ($dto): EnrollmentTransition {
            $enrollment = Enrollment::query()
                ->whereKey($dto->enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $enrollment->status;

            throw_unless(
                $fromStatus === $dto->fromStatus,
                OverrideEnrollmentStatusException::statusChanged($dto->fromStatus, $fromStatus),
            );

            throw_unless(
                $this->isAllowed($fromStatus, $dto->toStatus),
                OverrideEnrollmentStatusException::overrideNotAllowed($fromStatus, $dto->toStatus),
            );

            $attributes = ['status' => $dto->toStatus];

            if ($timestampColumn = $dto->toStatus->timestampColumn()) {
                $attributes[$timestampColumn] = now();
            }

            $enrollment->update($attributes);

            return EnrollmentTransition::query()->create([
                'enrollment_id' => $enrollment->id,
                'from_status' => $fromStatus,
                'to_status' => $dto->toStatus,
                'actor_id' => $dto->actorId,
                'triggered_by' => TriggeredBy::Admin,
                'reason' => $dto->reason,
            ]);
        });
    }

    private function isAllowed(EnrollmentStatus $from, EnrollmentStatus $to): bool
    {
        return array_any(self::ALLOWED_OVERRIDES, fn (array $pair) => $pair['from'] === $from && $pair['to'] === $to);
    }
}
