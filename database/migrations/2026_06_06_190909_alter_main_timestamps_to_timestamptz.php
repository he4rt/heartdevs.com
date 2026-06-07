<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // personal_access_tokens
        $this->alterColumn('personal_access_tokens', 'created_at');
        $this->alterColumn('personal_access_tokens', 'updated_at');
        $this->alterColumn('personal_access_tokens', 'last_used_at');
        $this->alterColumn('personal_access_tokens', 'expires_at');

        // notifications
        $this->alterColumn('notifications', 'created_at');
        $this->alterColumn('notifications', 'updated_at');
        $this->alterColumn('notifications', 'read_at');

        // media
        $this->alterColumn('media', 'created_at');
        $this->alterColumn('media', 'updated_at');

        // failed_jobs
        $this->alterColumn('failed_jobs', 'failed_at');

        // telescope_entries
        $this->alterColumn('telescope_entries', 'created_at');
    }

    /**
     * @param  'America/Sao_Paulo'|'UTC'  $timezone
     */
    private function alterColumn(string $table, string $column, string $timezone = 'America/Sao_Paulo'): void
    {
        $isTimestamp = DB::scalar(
            "SELECT data_type = 'timestamp without time zone'
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
            [$table, $column],
        );

        if (!$isTimestamp) {
            return;
        }

        DB::statement(
            sprintf("ALTER TABLE %s ALTER COLUMN \"%s\" TYPE timestamptz USING \"%s\" AT TIME ZONE '%s'", $table, $column, $column, $timezone)
        );
    }
};
