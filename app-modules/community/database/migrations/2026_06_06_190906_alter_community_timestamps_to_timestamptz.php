<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // meeting_types
        $this->alterColumn('meeting_types', 'created_at');
        $this->alterColumn('meeting_types', 'updated_at');

        // meetings
        $this->alterColumn('meetings', 'created_at');
        $this->alterColumn('meetings', 'updated_at');
        $this->alterColumn('meetings', 'starts_at');
        $this->alterColumn('meetings', 'ends_at');

        // meeting_participants
        $this->alterColumn('meeting_participants', 'created_at');
        $this->alterColumn('meeting_participants', 'updated_at');
        $this->alterColumn('meeting_participants', 'attend_at');

        // feedbacks
        $this->alterColumn('feedbacks', 'created_at');
        $this->alterColumn('feedbacks', 'updated_at');

        // feedback_reviews
        $this->alterColumn('feedback_reviews', 'created_at');
        $this->alterColumn('feedback_reviews', 'updated_at');
        $this->alterColumn('feedback_reviews', 'received_at');
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
