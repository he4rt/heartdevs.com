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

    public static function invalidCheckInStatus(): self
    {
        return new self(
            __('events::exceptions.invalid_check_in_status'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function checkInOutsideEventDateRange(): self
    {
        return new self(
            __('events::exceptions.check_in_outside_event_date_range'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function alreadyCheckedInForDate(): self
    {
        return new self(
            __('events::exceptions.already_checked_in_for_date'),
            Response::HTTP_CONFLICT,
        );
    }

    public static function invalidCheckInActor(): self
    {
        return new self(
            __('events::exceptions.invalid_check_in_actor'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
