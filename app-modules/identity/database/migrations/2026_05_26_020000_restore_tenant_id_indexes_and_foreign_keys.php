<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement("SET maintenance_work_mem = '1GB'");

        // Tier 1 — critical: large tables + high-frequency queries
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_tenant_id_idx ON messages (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_tenant_sent_at_idx ON messages (tenant_id, sent_at DESC)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS tenant_users_tenant_id_idx ON tenant_users (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS tenant_users_user_id_idx ON tenant_users (user_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS external_identities_provider_account_idx ON external_identities (provider, external_account_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS voice_messages_tenant_id_idx ON voice_messages (tenant_id)');

        // Tier 2 — medium tables, listing queries
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS message_embeds_tenant_id_idx ON message_embeds (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS message_mentions_tenant_id_idx ON message_mentions (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS message_attachments_tenant_id_idx ON message_attachments (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_reactions_tenant_id_idx ON activity_reactions (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS characters_tenant_id_idx ON characters (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS external_identities_tenant_id_idx ON external_identities (tenant_id)');

        // Tier 3 — small tables, completeness
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_timeline_tenant_id_idx ON activity_timeline (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS badges_tenant_id_idx ON badges (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS characters_badges_tenant_id_idx ON characters_badges (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS discord_guilds_tenant_id_idx ON discord_guilds (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS feedback_reviews_tenant_id_idx ON feedback_reviews (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS feedbacks_tenant_id_idx ON feedbacks (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS interactions_tenant_id_idx ON interactions (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS meetings_tenant_id_idx ON meetings (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS membership_events_tenant_id_idx ON membership_events (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS message_threads_tenant_id_idx ON message_threads (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_actions_tenant_id_idx ON moderation_actions (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_appeals_tenant_id_idx ON moderation_appeals (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_audit_log_tenant_id_idx ON moderation_audit_log (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_cases_tenant_id_idx ON moderation_cases (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_events_tenant_id_idx ON moderation_events (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_rules_tenant_id_idx ON moderation_rules (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS seasons_tenant_id_idx ON seasons (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS seasons_rankings_tenant_id_idx ON seasons_rankings (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS twitch_event_logs_tenant_id_idx ON twitch_event_logs (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS twitch_subscriptions_tenant_id_idx ON twitch_subscriptions (tenant_id)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS user_profiles_tenant_id_idx ON user_profiles (tenant_id)');

        DB::statement('SET maintenance_work_mem = DEFAULT');
    }

    public function down(): void
    {
        $indexes = [
            'messages_tenant_id_idx',
            'messages_tenant_sent_at_idx',
            'tenant_users_tenant_id_idx',
            'tenant_users_user_id_idx',
            'external_identities_provider_account_idx',
            'external_identities_tenant_id_idx',
            'voice_messages_tenant_id_idx',
            'message_embeds_tenant_id_idx',
            'message_mentions_tenant_id_idx',
            'message_attachments_tenant_id_idx',
            'activity_reactions_tenant_id_idx',
            'characters_tenant_id_idx',
            'activity_timeline_tenant_id_idx',
            'badges_tenant_id_idx',
            'characters_badges_tenant_id_idx',
            'discord_guilds_tenant_id_idx',
            'feedback_reviews_tenant_id_idx',
            'feedbacks_tenant_id_idx',
            'interactions_tenant_id_idx',
            'meetings_tenant_id_idx',
            'membership_events_tenant_id_idx',
            'message_threads_tenant_id_idx',
            'moderation_actions_tenant_id_idx',
            'moderation_appeals_tenant_id_idx',
            'moderation_audit_log_tenant_id_idx',
            'moderation_cases_tenant_id_idx',
            'moderation_events_tenant_id_idx',
            'moderation_rules_tenant_id_idx',
            'seasons_tenant_id_idx',
            'seasons_rankings_tenant_id_idx',
            'twitch_event_logs_tenant_id_idx',
            'twitch_subscriptions_tenant_id_idx',
            'user_profiles_tenant_id_idx',
        ];

        foreach ($indexes as $index) {
            DB::statement(sprintf('DROP INDEX IF EXISTS %s', $index));
        }
    }
};
