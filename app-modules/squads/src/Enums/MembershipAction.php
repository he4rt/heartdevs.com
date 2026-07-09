<?php

declare(strict_types=1);

namespace He4rt\Squads\Enums;

enum MembershipAction: string
{
    case Join = 'join';
    case Leave = 'leave';
    case Promote = 'promote';
    case Demote = 'demote';
}
