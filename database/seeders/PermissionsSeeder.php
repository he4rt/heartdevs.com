<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Idempotent RBAC baseline. Seeded once per test process via TestCase::$seeder
 * (RefreshDatabase forwards it to migrate:fresh --seeder), and reused by
 * DatabaseSeeder for local/prod seeding.
 */
final class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('sync:permissions');
    }
}
