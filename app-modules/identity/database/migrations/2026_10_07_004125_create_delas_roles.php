<?php

declare(strict_types=1);

use He4rt\Identity\Authorization\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<UserRole> */
    private const array ROLES = [UserRole::DelasModerator, UserRole::DelasLead];

    public function up(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role->value, UserRole::GUARD);
        }

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::query()
            ->whereIn('name', array_map(fn (UserRole $role): string => $role->value, self::ROLES))
            ->where('guard_name', UserRole::GUARD)
            ->delete();

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
