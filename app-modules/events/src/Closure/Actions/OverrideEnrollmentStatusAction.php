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
    private const array ALLOWED_OVERRIDES = [
        ['from' => EnrollmentStatus::NoShow, 'to' => EnrollmentStatus::Attended],
        ['from' => EnrollmentStatus::Confirmed, 'to' => EnrollmentStatus::CheckedIn],
    ];

    /** @return list<EnrollmentStatus> */
    public static function allowedTargetsFor(EnrollmentStatus $from): array
    {
        return array_values(array_map(
            static fn (array $pair): EnrollmentStatus => $pair['to'],
            array_filter(self::ALLOWED_OVERRIDES, static fn (array $pair): bool => $pair['from'] === $from),
        ));
    }

    /**
     * @throws OverrideEnrollmentStatusException
     * @throws Throwable
     */
    public function handle(OverrideEnrollmentStatusDTO $dto): EnrollmentTransition
    {
        if (mb_trim($dto->reason) === '') {
            throw OverrideEnrollmentStatusException::reasonRequired();
        }

        return DB::transaction(function () use ($dto): EnrollmentTransition {
            $enrollment = Enrollment::query()
                ->whereKey($dto->enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $enrollment->status;

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
        return array_any(self::ALLOWED_OVERRIDES, fn ($pair) => $pair['from'] === $from && $pair['to'] === $to);
    }
}
