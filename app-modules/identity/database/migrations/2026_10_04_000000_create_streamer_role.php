<?php

declare(strict_types=1);

use He4rt\Identity\Authorization\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Role::findOrCreate(UserRole::Streamer->value, UserRole::GUARD);

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::query()
            ->where('name', UserRole::Streamer->value)
            ->where('guard_name', UserRole::GUARD)
            ->delete();

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
