<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions\Actions;

use He4rt\Identity\Permissions\Role;

final readonly class DeleteRole
{
    public function handle(Role $role): void
    {
        $role->delete();
    }
}
