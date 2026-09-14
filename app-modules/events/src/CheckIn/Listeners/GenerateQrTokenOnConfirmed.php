<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Listeners;

use He4rt\Events\CheckIn\Actions\GenerateQrTokenAction;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Models\Enrollment;

final readonly class GenerateQrTokenOnConfirmed
{
    public function handle(EnrollmentConfirmed $event): void
    {
        $enrollment = Enrollment::query()->findOrFail($event->enrollmentId);

        resolve(GenerateQrTokenAction::class)->handle($enrollment);
    }
}
