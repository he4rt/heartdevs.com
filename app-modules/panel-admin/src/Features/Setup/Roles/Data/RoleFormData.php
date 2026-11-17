<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Data;

use He4rt\Identity\Permissions\Role;
use Spatie\LaravelData\Data;

final class RoleFormData extends Data
{
    /**
     * @param  array<int, string>  $permissionIds
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $isSystem,
        public array $permissionIds,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
            isSystem: $role->isSystem(),
            permissionIds: $role->permissions()->pluck('id')->map(strval(...))->values()->all(),
        );
    }
}
