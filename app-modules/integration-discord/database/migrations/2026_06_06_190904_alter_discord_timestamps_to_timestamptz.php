<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // discord_event_logs
        $this->alterColumn('discord_event_logs', 'created_at');
        $this->alterColumn('discord_event_logs', 'updated_at');

        // discord_guilds
        $this->alterColumn('discord_guilds', 'created_at');
        $this->alterColumn('discord_guilds', 'updated_at');
        $this->alterColumn('discord_guilds', 'synced_at');

        // discord_channels
        $this->alterColumn('discord_channels', 'created_at');
        $this->alterColumn('discord_channels', 'updated_at');

        // discord_roles
        $this->alterColumn('discord_roles', 'created_at');
        $this->alterColumn('discord_roles', 'updated_at');

        // discord_members
        $this->alterColumn('discord_members', 'created_at');
        $this->alterColumn('discord_members', 'updated_at');
        $this->alterColumn('discord_members', 'left_at');
        // Discord API — always UTC
        $this->alterColumn('discord_members', 'joined_at', 'UTC');
        $this->alterColumn('discord_members', 'premium_since', 'UTC');
        $this->alterColumn('discord_members', 'communication_disabled_until', 'UTC');

        // discord_member_roles
        $this->alterColumn('discord_member_roles', 'created_at');
        $this->alterColumn('discord_member_roles', 'updated_at');
        $this->alterColumn('discord_member_roles', 'assigned_at');

        // discord_member_role_history
        $this->alterColumn('discord_member_role_history', 'created_at');
        $this->alterColumn('discord_member_role_history', 'updated_at');
        $this->alterColumn('discord_member_role_history', 'occurred_at');
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
