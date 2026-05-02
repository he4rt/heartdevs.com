<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum AppealStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Upheld = 'upheld';
    case Overturned = 'overturned';
}
