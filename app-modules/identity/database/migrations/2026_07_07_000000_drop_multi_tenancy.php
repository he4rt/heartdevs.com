<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated destructive migration that removes multi-tenancy from the
 * schema. It collapses every tenant-scoped unique/secondary index down to its
 * global equivalent, drops the `tenant_id` column from every table that still
 * carries one, and finally drops the `tenant_users` and `tenants` tables.
 *
 * Ordering matters: indexes and foreign keys that reference `tenant_id` are
 * dropped BEFORE the columns themselves, and every step is guarded so the
 * migration is safe to run against a partially-migrated database.
 */
return new class extends Migration
{
    /**
     * Tables that still carry a `tenant_id` column to be dropped.
     *
     * `external_identities` keeps the legacy `providers_tenant_id_foreign`
     * constraint name (the table was created as `providers`). `activity_timeline`
     * has a bare `foreignUuid` with no FK constraint, and `moderation_audit_log`
     * has no FK at all — the guarded DROP CONSTRAINT IF EXISTS is a no-op there.
     *
     * @var list<string>
     */
    private array $tenantColumnTables = [
        'external_identities',
        'messages',
        'voice_messages',
        'characters',
        'feedbacks',
        'feedback_reviews',
        'meetings',
        'badges',
        'characters_badges',
        'seasons_rankings',
        'seasons',
        'activity_reactions',
        'message_mentions',
        'message_threads',
        'message_attachments',
        'message_embeds',
        'membership_events',
        'interactions',
        'moderation_events',
        'moderation_cases',
        'moderation_rules',
        'moderation_audit_log',
        'moderation_actions',
        'moderation_appeals',
        'github_repositories',
        'github_contributions',
        'twitch_subscriptions',
        'twitch_event_logs',
        'discord_guilds',
        'user_profiles',
        'activity_timeline',
    ];

    public function up(): void
    {
        $this->collapseTenantScopedIndexes();
        $this->dropTenantColumns();
        $this->dropTenantTables();
    }

    /**
     * Best-effort, NOT fully reversible.
     *
     * The tenant relationships, the pivot rows, and the original tenant-scoped
     * unique constraints cannot be reconstructed because the source data is
     * gone. This only recreates a nullable `tenant_id` column on each table so a
     * rollback does not hard-fail; it does not restore any foreign keys, the
     * collapsed indexes, or the `tenants`/`tenant_users` tables.
     */
    public function down(): void
    {
        foreach ($this->tenantColumnTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, static function (Blueprint $blueprint): void {
                $blueprint->uuid('tenant_id')->nullable();
            });
        }
    }

    /**
     * 1. Drop tenant-scoped indexes and recreate the collapsed global
     *    equivalents. Raw SQL with IF EXISTS / IF NOT EXISTS keeps this
     *    idempotent regardless of how each index was originally created.
     */
    private function collapseTenantScopedIndexes(): void
    {
        $statements = [
            // messages: unique[tenant_id, provider_message_id] -> unique(provider_message_id)
            'ALTER TABLE messages DROP CONSTRAINT IF EXISTS messages_tenant_provider_message_id_unique',
            'DROP INDEX IF EXISTS messages_tenant_provider_message_id_unique',
            'CREATE UNIQUE INDEX IF NOT EXISTS messages_provider_message_id_unique ON messages (provider_message_id)',
            'DROP INDEX IF EXISTS messages_tenant_sent_at_idx',
            'DROP INDEX IF EXISTS messages_tenant_channel_sent_at_idx',
            'DROP INDEX IF EXISTS messages_tenant_kind_idx',

            // voice_messages: partial unique (tenant_id, provider_message_id) -> (provider_message_id)
            'ALTER TABLE voice_messages DROP CONSTRAINT IF EXISTS voice_messages_tenant_provider_message_id_unique',
            'DROP INDEX IF EXISTS voice_messages_tenant_provider_message_id_unique',
            'CREATE UNIQUE INDEX IF NOT EXISTS voice_messages_provider_message_id_unique ON voice_messages (provider_message_id) WHERE provider_message_id IS NOT NULL',

            // moderation_events: partial unique -> (provider_message_id) + drop tenant secondary
            'ALTER TABLE moderation_events DROP CONSTRAINT IF EXISTS moderation_events_tenant_provider_message_id_unique',
            'DROP INDEX IF EXISTS moderation_events_tenant_provider_message_id_unique',
            'CREATE UNIQUE INDEX IF NOT EXISTS moderation_events_provider_message_id_unique ON moderation_events (provider_message_id) WHERE provider_message_id IS NOT NULL',
            'DROP INDEX IF EXISTS moderation_events_tenant_id_type_occurred_at_index',

            // membership_events: partial unique -> (provider_message_id) + drop tenant secondary
            'ALTER TABLE membership_events DROP CONSTRAINT IF EXISTS membership_events_tenant_provider_unique',
            'DROP INDEX IF EXISTS membership_events_tenant_provider_unique',
            'CREATE UNIQUE INDEX IF NOT EXISTS membership_events_provider_message_id_unique ON membership_events (provider_message_id) WHERE provider_message_id IS NOT NULL',
            'DROP INDEX IF EXISTS membership_events_tenant_kind_time_idx',

            // message_threads: unique[tenant_id, provider_thread_id] -> unique(provider_thread_id)
            'ALTER TABLE message_threads DROP CONSTRAINT IF EXISTS message_threads_tenant_provider_unique',
            'DROP INDEX IF EXISTS message_threads_tenant_provider_unique',
            'CREATE UNIQUE INDEX IF NOT EXISTS message_threads_provider_thread_id_unique ON message_threads (provider_thread_id)',

            // interactions: unique[tenant_id, provider, external_ref] -> unique(provider, external_ref) + drop [tenant_id, occurred_at]
            'ALTER TABLE interactions DROP CONSTRAINT IF EXISTS uniq_interactions_tenant_provider_ref',
            'DROP INDEX IF EXISTS uniq_interactions_tenant_provider_ref',
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_interactions_provider_ref ON interactions (provider, external_ref)',
            'DROP INDEX IF EXISTS idx_interactions_tenant',

            // github_repositories: unique[tenant_id, full_name] -> unique(full_name)
            'ALTER TABLE github_repositories DROP CONSTRAINT IF EXISTS uniq_github_repositories_tenant_repo',
            'DROP INDEX IF EXISTS uniq_github_repositories_tenant_repo',
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_github_repositories_full_name ON github_repositories (full_name)',

            // github_contributions: unique[tenant_id, repo, type, external_ref] -> unique(repo, type, external_ref) + drop tenant time/type indexes
            'ALTER TABLE github_contributions DROP CONSTRAINT IF EXISTS uniq_github_contributions_ref',
            'DROP INDEX IF EXISTS uniq_github_contributions_ref',
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_github_contributions_repo_type_ref ON github_contributions (repo, type, external_ref)',
            'DROP INDEX IF EXISTS idx_github_contributions_tenant_time',
            'DROP INDEX IF EXISTS idx_github_contributions_type_time',

            // user_profiles: unique[user_id, tenant_id] -> unique(user_id) + partial (tenant_id, user_id) -> (user_id)
            'ALTER TABLE user_profiles DROP CONSTRAINT IF EXISTS user_profiles_user_id_tenant_id_unique',
            'DROP INDEX IF EXISTS user_profiles_user_id_tenant_id_unique',
            'CREATE UNIQUE INDEX IF NOT EXISTS user_profiles_user_id_unique ON user_profiles (user_id)',
            'DROP INDEX IF EXISTS user_profiles_available_for_proposals_index',
            'CREATE INDEX IF NOT EXISTS user_profiles_available_for_proposals_index ON user_profiles (user_id) WHERE available_for_proposals = true',

            // Remaining tenant-scoped secondary indexes (no collapsed replacement needed).
            'DROP INDEX IF EXISTS providers_tenant_id_model_type_model_id_index',
            'DROP INDEX IF EXISTS activity_reactions_tenant_emoji_time_idx',
            'DROP INDEX IF EXISTS message_mentions_tenant_identity_idx',
            'DROP INDEX IF EXISTS message_mentions_tenant_provider_idx',
            'DROP INDEX IF EXISTS message_embeds_tenant_domain_idx',
            'DROP INDEX IF EXISTS message_embeds_tenant_kind_idx',
            'DROP INDEX IF EXISTS idx_cases_queue',
            'DROP INDEX IF EXISTS idx_rules_active',
            'DROP INDEX IF EXISTS idx_audit_tenant_date',
            'DROP INDEX IF EXISTS activity_timeline_tenant_feed_index',
            'DROP INDEX IF EXISTS activity_timeline_feed_composite_index',
            'DROP INDEX IF EXISTS twitch_subscriptions_tenant_id_index',
        ];

        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }

    /**
     * 2. Drop the tenant_id foreign key (if present) then the column itself.
     */
    private function dropTenantColumns(): void
    {
        foreach ($this->tenantColumnTables as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            // Conventional FK name, plus the legacy `providers_*` name kept by
            // external_identities. Both guarded so tables without an FK no-op.
            DB::statement(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s_tenant_id_foreign', $table, $table));
            DB::statement(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS providers_tenant_id_foreign', $table));

            Schema::table($table, static function (Blueprint $blueprint): void {
                $blueprint->dropColumn('tenant_id');
            });
        }
    }

    /**
     * 3. Drop the tenant pivot and the tenants table. Any lingering foreign key
     *    still referencing `tenants` (e.g. from tables without an in-repo
     *    migration) is dropped first so the DROP TABLE cannot fail.
     */
    private function dropTenantTables(): void
    {
        if (Schema::hasTable('tenants')) {
            $referencingConstraints = DB::select(<<<'SQL'
                SELECT tc.table_name, tc.constraint_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.constraint_column_usage ccu
                    ON tc.constraint_name = ccu.constraint_name
                    AND tc.table_schema = ccu.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                  AND ccu.table_name = 'tenants'
            SQL);

            foreach ($referencingConstraints as $constraint) {
                DB::statement(sprintf(
                    'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
                    $constraint->table_name,
                    $constraint->constraint_name,
                ));
            }
        }

        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('tenants');
    }
};
