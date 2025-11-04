<?php

declare(strict_types=1);

namespace He4rt\Events\Enums;

enum AttendingStatusEnum: string
{
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Waitlist = 'waitlist';
}
