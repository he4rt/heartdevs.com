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
}
