<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions;

use App\Models\Concerns\CapturesActivityContext;
use He4rt\Identity\Database\Factories\Permissions\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role as BaseRole;

/**
 * @property-read string $id
 * @property string $name
 * @property string $guard_name
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Collection<int, Permission> $permissions
 */
#[UsePolicy(class: RolePolicy::class)]
#[UseFactory(factoryClass: RoleFactory::class)]
#[Table(name: 'identity_roles')]
final class Role extends BaseRole
{
    use CapturesActivityContext;

    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * System roles come from {@see Roles}. sync:permissions rewrites their permissions from config/rbac.php,
     * and code checks them by name, so they cannot be renamed, edited or deleted.
     */
    public function isSystem(): bool
    {
        return Roles::tryFrom($this->name) instanceof Roles;
    }
}
