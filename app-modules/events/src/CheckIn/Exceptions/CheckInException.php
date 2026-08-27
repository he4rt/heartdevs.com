<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class CheckInException extends Exception
{
    public static function invalidCheckInStatus(): self
    {
        return new self(
            __('events::check_in.invalid_check_in_status'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function checkInOutsideEventDateRange(): self
    {
        return new self(
            __('events::check_in.check_in_outside_event_date_range'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function alreadyCheckedInForDate(): self
    {
        return new self(
            __('events::check_in.already_checked_in_for_date'),
            Response::HTTP_CONFLICT,
        );
    }

    public static function invalidCheckInActor(): self
    {
        return new self(
            __('events::check_in.invalid_check_in_actor'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function qrTokenNotFound(): self
    {
        return new self(
            __('events::check_in.qr_token_not_found'),
            Response::HTTP_NOT_FOUND,
        );
    }

    public static function qrTokenExpired(): self
    {
        return new self(
            __('events::check_in.qr_token_expired'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function invalidCheckInCode(): self
    {
        return new self(
            __('events::check_in.invalid_check_in_code'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function invalidCheckInCodeFormat(): self
    {
        return new self(
            __('events::check_in.invalid_check_in_code_format'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function checkInCodeExpired(): self
    {
        return new self(
            __('events::check_in.check_in_code_expired'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function checkInCodeExhausted(): self
    {
        return new self(
            __('events::check_in.check_in_code_exhausted'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function checkInCodeWrongDate(): self
    {
        return new self(
            __('events::check_in.check_in_code_wrong_date'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function checkInCodeRateLimited(int $seconds): self
    {
        return new self(
            __('events::check_in.check_in_code_rate_limited', ['seconds' => $seconds]),
            Response::HTTP_TOO_MANY_REQUESTS,
        );
    }

    public static function botNoActiveEnrollment(): self
    {
        return new self(
            __('events::check_in.bot_no_active_enrollment'),
            Response::HTTP_NOT_FOUND,
        );
    }

    public static function botMultipleActiveEvents(): self
    {
        return new self(
            __('events::check_in.bot_multiple_active_events'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
