<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions;

enum Roles: string
{
    case SuperAdmin = 'super_admin';

    case User = 'user';
}
