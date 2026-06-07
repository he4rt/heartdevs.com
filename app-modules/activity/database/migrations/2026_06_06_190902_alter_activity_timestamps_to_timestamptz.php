<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // messages (skip sent_at — already timestamptz)
        $this->alterColumn('messages', 'created_at');
        $this->alterColumn('messages', 'updated_at');
        $this->alterColumn('messages', 'edited_at');

        // voice_messages (skip occurred_at — already timestamptz)
        $this->alterColumn('voice_messages', 'created_at');
        $this->alterColumn('voice_messages', 'updated_at');

        // interactions
        $this->alterColumn('interactions', 'created_at');
        $this->alterColumn('interactions', 'updated_at');
        $this->alterColumn('interactions', 'occurred_at');
        $this->alterColumn('interactions', 'reviewed_at');

        // moderation_events
        $this->alterColumn('moderation_events', 'created_at');
        $this->alterColumn('moderation_events', 'updated_at');
        $this->alterColumn('moderation_events', 'occurred_at');

        // activity_reactions
        $this->alterColumn('activity_reactions', 'created_at');
        $this->alterColumn('activity_reactions', 'updated_at');

        // message_mentions
        $this->alterColumn('message_mentions', 'created_at');
        $this->alterColumn('message_mentions', 'updated_at');

        // message_threads
        $this->alterColumn('message_threads', 'created_at');
        $this->alterColumn('message_threads', 'updated_at');

        // message_attachments
        $this->alterColumn('message_attachments', 'created_at');
        $this->alterColumn('message_attachments', 'updated_at');

        // message_embeds
        $this->alterColumn('message_embeds', 'created_at');
        $this->alterColumn('message_embeds', 'updated_at');

        // membership_events
        $this->alterColumn('membership_events', 'created_at');
        $this->alterColumn('membership_events', 'updated_at');
        $this->alterColumn('membership_events', 'occurred_at');

        // activity_timeline
        $this->alterColumn('activity_timeline', 'created_at');
        $this->alterColumn('activity_timeline', 'updated_at');

        // activity_post_entries
        $this->alterColumn('activity_post_entries', 'created_at');
        $this->alterColumn('activity_post_entries', 'updated_at');
        $this->alterColumn('activity_post_entries', 'deleted_at');
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
