<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Exceptions;

use Exception;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use Symfony\Component\HttpFoundation\Response;

final class EnrollmentException extends Exception
{
    public static function invalidTransition(EnrollmentStatus $from, EnrollmentStatus $to): self
    {
        return new self(
            __('events::exceptions.invalid_transition', ['from' => $from->value, 'to' => $to->value]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

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

    public static function responseMessageNotImplemented(EnrollmentStatus $status): self
    {
        return new self(
            __('events::exceptions.response_message_not_implemented', ['status' => $status->value]),
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    public static function enrollmentNotPending(): self
    {
        return new self(
            __('events::exceptions.enrollment_not_pending'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function applicationDataInvalid(): self
    {
        return new self(
            __('events::exceptions.application_data_invalid'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
