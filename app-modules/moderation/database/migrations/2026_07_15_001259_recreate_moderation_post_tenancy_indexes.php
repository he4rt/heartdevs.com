<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $statements = [
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_cases_status_priority_created_at_index ON moderation_cases (status, priority, created_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_rules_is_active_index ON moderation_rules (is_active)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_audit_log_created_at_index ON moderation_audit_log (created_at)',
        ];

        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }
};
