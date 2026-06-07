<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // characters
        $this->alterColumn('characters', 'created_at');
        $this->alterColumn('characters', 'updated_at');
        $this->alterColumn('characters', 'daily_bonus_claimed_at');

        // badges
        $this->alterColumn('badges', 'created_at');
        $this->alterColumn('badges', 'updated_at');

        // characters_badges
        $this->alterColumn('characters_badges', 'claimed_at');

        // seasons_rankings
        $this->alterColumn('seasons_rankings', 'created_at');
        $this->alterColumn('seasons_rankings', 'updated_at');

        // seasons
        $this->alterColumn('seasons', 'created_at');
        $this->alterColumn('seasons', 'updated_at');
        $this->alterColumn('seasons', 'started_at');
        $this->alterColumn('seasons', 'ended_at');

        // characters_leveling_logs (skip characters_wallet — already timestamptz)
        $this->alterColumn('characters_leveling_logs', 'created_at');
        $this->alterColumn('characters_leveling_logs', 'updated_at');
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
