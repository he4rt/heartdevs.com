<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recreates the non-tenant equivalents of the moderation-module secondary
 * indexes dropped by `2026_07_07_000000_drop_multi_tenancy` without a
 * replacement: the queue ordering index on cases, the active-rules filter
 * (tenant_id was the trailing column, but the leading is_active predicate
 * lost its index too), and the audit-log date index.
 *
 * `CREATE INDEX CONCURRENTLY` avoids blocking writes during the build and
 * cannot run inside a transaction.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Index name => CREATE statement. Names are keys so `down()` cannot
     * drift from `up()`.
     *
     * @var array<string, string>
     */
    private array $indexes = [
        // was idx_cases_queue (tenant_id, status, priority, created_at)
        'moderation_cases_status_priority_created_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_cases_status_priority_created_at_index ON moderation_cases (status, priority, created_at)',
        // was idx_rules_active (is_active, tenant_id)
        'moderation_rules_is_active_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_rules_is_active_index ON moderation_rules (is_active)',
        // was idx_audit_tenant_date (tenant_id, created_at)
        'moderation_audit_log_created_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_audit_log_created_at_index ON moderation_audit_log (created_at)',
    ];

    public function up(): void
    {
        foreach ($this->indexes as $statement) {
            DB::statement($statement);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->indexes) as $index) {
            DB::statement(sprintf('DROP INDEX CONCURRENTLY IF EXISTS %s', $index));
        }
    }
};
