<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $childTables = [
        'activity_reactions',
        'activity_timeline',
        'badges',
        'characters',
        'characters_badges',
        'discord_guilds',
        'external_identities',
        'feedback_reviews',
        'feedbacks',
        'interactions',
        'meetings',
        'membership_events',
        'message_attachments',
        'message_embeds',
        'message_mentions',
        'message_threads',
        'messages',
        'moderation_actions',
        'moderation_appeals',
        'moderation_audit_log',
        'moderation_cases',
        'moderation_events',
        'moderation_rules',
        'seasons',
        'seasons_rankings',
        'tenant_users',
        'twitch_event_logs',
        'twitch_subscriptions',
        'user_profiles',
        'voice_messages',
    ];

    public function up(): void
    {
        DB::statement('ALTER TABLE tenants ADD COLUMN new_id uuid DEFAULT gen_random_uuid()');
        DB::statement('UPDATE tenants SET new_id = gen_random_uuid() WHERE new_id IS NULL');

        $mapping = DB::table('tenants')->pluck('new_id', 'id');

        foreach ($this->childTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::statement(sprintf('ALTER TABLE %s ADD COLUMN new_tenant_id uuid', $table));

            foreach ($mapping as $oldId => $newUuid) {
                DB::statement(sprintf('UPDATE %s SET new_tenant_id = ?::uuid WHERE tenant_id = ?', $table), [$newUuid, $oldId]);
            }

            $this->dropForeignKeyIfExists($table, 'tenant_id');

            DB::statement(sprintf('ALTER TABLE %s DROP COLUMN tenant_id', $table));
            DB::statement(sprintf('ALTER TABLE %s RENAME COLUMN new_tenant_id TO tenant_id', $table));
        }

        DB::statement('ALTER TABLE tenants DROP CONSTRAINT tenants_pkey');
        DB::statement('ALTER TABLE tenants DROP COLUMN id');
        DB::statement('ALTER TABLE tenants RENAME COLUMN new_id TO id');
        DB::statement('ALTER TABLE tenants ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE tenants ALTER COLUMN id SET DEFAULT gen_random_uuid()');
        DB::statement('ALTER TABLE tenants ALTER COLUMN id SET NOT NULL');
        DB::statement('DROP SEQUENCE IF EXISTS tenants_id_seq');
    }

    public function down(): void
    {
        // Irreversible — integer ID mapping is lost
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $constraints = DB::select("
            SELECT con.conname
            FROM pg_constraint con
            JOIN pg_attribute att ON att.attnum = ANY(con.conkey) AND att.attrelid = con.conrelid
            WHERE con.conrelid = ?::regclass
            AND att.attname = ?
            AND con.contype = 'f'
        ", [$table, $column]);

        foreach ($constraints as $constraint) {
            DB::statement(sprintf('ALTER TABLE %s DROP CONSTRAINT %s', $table, $constraint->conname));
        }
    }
};
