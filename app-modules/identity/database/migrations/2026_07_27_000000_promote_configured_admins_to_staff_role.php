<?php

declare(strict_types=1);

use He4rt\Identity\User\Enums\Role;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfills users listed in HE4RT_ADMINS_USERNAMES (isAdmin() bypass)
     * that don't already have Staff/Compliance role, so panel authorization
     * relies on a single source of truth (role) instead of the env list.
     */
    public function up(): void
    {
        $usernames = User::configuredAdminUsernames();

        if ($usernames === []) {
            return;
        }

        DB::table('users')
            ->whereIn('username', $usernames)
            ->whereNotIn('role', [Role::Staff->value, Role::Compliance->value])
            ->update(['role' => Role::Staff->value]);
    }

    public function down(): void
    {
        // Data backfill, not reversible.
    }
};
