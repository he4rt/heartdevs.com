<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Post-switch tables — data always UTC

        // twitch_event_logs
        $this->alterColumn('twitch_event_logs', 'created_at', 'UTC');
        $this->alterColumn('twitch_event_logs', 'updated_at', 'UTC');

        // twitch_subscriptions
        $this->alterColumn('twitch_subscriptions', 'created_at', 'UTC');
        $this->alterColumn('twitch_subscriptions', 'updated_at', 'UTC');
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
