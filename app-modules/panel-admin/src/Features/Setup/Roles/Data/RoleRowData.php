<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Data;

use He4rt\Identity\Permissions\Role;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

final class RoleRowData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $label,
        public string $guardName,
        public int $permissionsCount,
        public int $usersCount,
        public bool $isSystem,
        public string $updatedAt,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
            label: Str::headline($role->name),
            guardName: $role->guard_name,
            permissionsCount: (int) $role->getAttribute('permissions_count'),
            usersCount: (int) $role->getAttribute('users_count'),
            isSystem: $role->isSystem(),
            updatedAt: $role->updated_at->toIso8601String(),
        );
    }
}
