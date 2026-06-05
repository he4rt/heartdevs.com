<?php

declare(strict_types=1);

namespace He4rt\Events\Closure\Exceptions;

use Exception;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use Symfony\Component\HttpFoundation\Response;

final class OverrideEnrollmentStatusException extends Exception
{
    public static function reasonRequired(): self
    {
        return new self(
            __('events::exceptions.override_reason_required'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function overrideNotAllowed(EnrollmentStatus $from, EnrollmentStatus $to): self
    {
        return new self(
            __('events::exceptions.override_not_allowed', ['from' => $from->value, 'to' => $to->value]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
