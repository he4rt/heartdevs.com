<?php

declare(strict_types=1);

namespace He4rt\Squads\Enums;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
