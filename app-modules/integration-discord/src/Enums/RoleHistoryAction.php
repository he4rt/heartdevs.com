<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Enums;

enum RoleHistoryAction: string
{
    case Assigned = 'assigned';
    case Removed = 'removed';
}
