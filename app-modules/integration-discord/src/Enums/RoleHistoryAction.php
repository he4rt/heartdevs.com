<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Enums;

use App\Enums\Concerns\StringifyEnum;

enum RoleHistoryAction: string
{
    use StringifyEnum;

    case Assigned = 'assigned';
    case Removed = 'removed';
}
