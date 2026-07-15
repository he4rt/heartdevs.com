<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Restores `idx_github_contributions_type_time (type, occurred_at)`, dropped
 * by mistake in `2026_07_07_000000_drop_multi_tenancy`: the drop was labeled
 * "tenant time/type indexes", but this index never contained `tenant_id` —
 * the GitHub integration tables never carried tenancy at all. The original
 * definition lives in `2026_06_04_000002_create_github_contributions_table`.
 *
 * `CREATE INDEX CONCURRENTLY` avoids blocking writes during the build and
 * cannot run inside a transaction.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_github_contributions_type_time ON github_contributions (type, occurred_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_github_contributions_type_time');
    }
};
