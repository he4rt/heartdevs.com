<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum CaseStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Resolved = 'resolved';
    case Escalated = 'escalated';
    case Dismissed = 'dismissed';
}
