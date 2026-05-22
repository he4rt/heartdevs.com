<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class EnrollmentException extends Exception
{
    public static function alreadyEnrolled(): self
    {
        return new self(
            __('events::exceptions.already_enrolled'),
            Response::HTTP_CONFLICT,
        );
    }

    public static function eventPast(): self
    {
        return new self(
            __('events::exceptions.event_past'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function eventNotActive(): self
    {
        return new self(
            __('events::exceptions.event_not_active'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function invalidEnrollmentMethod(): self
    {
        return new self(
            __('events::exceptions.invalid_enrollment_method'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function eventFull(): self
    {
        return new self(
            __('events::exceptions.event_full'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
