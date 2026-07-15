<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recreates the non-tenant equivalents of the secondary indexes dropped by
 * `2026_07_07_000000_drop_multi_tenancy` for the activity-module tables. That
 * migration collapsed the unique indexes correctly but removed every
 * tenant-leading performance index without a replacement, leaving hot query
 * paths (marketing dashboards, timeline feed) on sequential scans.
 *
 * Also adds the never-existing `voice_messages` partial index used by the
 * marketing voice widgets, which always filter `state = 'joined'` over an
 * `occurred_at` window.
 *
 * `CREATE INDEX CONCURRENTLY` avoids blocking the Discord bot's continuous
 * writes during the build, and cannot run inside a transaction.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Index name => CREATE statement. Names are keys so `down()` cannot
     * drift from `up()`.
     *
     * @var array<string, string>
     */
    private array $indexes = [
        // messages: was messages_tenant_sent_at_idx (tenant_id, sent_at)
        'messages_sent_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_sent_at_index ON messages (sent_at)',
        // messages: was messages_tenant_channel_sent_at_idx (tenant_id, channel_id, sent_at)
        'messages_channel_id_sent_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_channel_id_sent_at_index ON messages (channel_id, sent_at)',
        // messages: was messages_tenant_kind_idx (tenant_id, kind)
        'messages_kind_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_kind_index ON messages (kind)',
        // voice_messages: net-new — marketing voice widgets always filter state = joined
        'voice_messages_joined_occurred_at_index' => "CREATE INDEX CONCURRENTLY IF NOT EXISTS voice_messages_joined_occurred_at_index ON voice_messages (occurred_at) WHERE state = 'joined'",
        // activity_timeline: was activity_timeline_tenant_feed_index (tenant_id, created_at)
        'activity_timeline_created_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_timeline_created_at_index ON activity_timeline (created_at)',
        // activity_timeline: was (tenant_id, parent_id, is_ignored, created_at)
        'activity_timeline_feed_composite_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_timeline_feed_composite_index ON activity_timeline (parent_id, is_ignored, created_at)',
        // activity_reactions: was activity_reactions_tenant_emoji_time_idx (tenant_id, emoji_key, created_at)
        'activity_reactions_emoji_key_created_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_reactions_emoji_key_created_at_index ON activity_reactions (emoji_key, created_at)',
        // message_mentions: was message_mentions_tenant_identity_idx (tenant_id, mentioned_identity_id)
        'message_mentions_mentioned_identity_id_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_mentions_mentioned_identity_id_index ON message_mentions (mentioned_identity_id)',
        // message_mentions: was message_mentions_tenant_provider_idx (tenant_id, mentioned_provider_account_id)
        'message_mentions_mentioned_provider_account_id_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_mentions_mentioned_provider_account_id_index ON message_mentions (mentioned_provider_account_id)',
        // message_embeds: was message_embeds_tenant_domain_idx (tenant_id, source_domain)
        'message_embeds_source_domain_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_embeds_source_domain_index ON message_embeds (source_domain)',
        // message_embeds: was message_embeds_tenant_kind_idx (tenant_id, kind)
        'message_embeds_kind_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_embeds_kind_index ON message_embeds (kind)',
        // membership_events: was membership_events_tenant_kind_time_idx (tenant_id, kind, occurred_at)
        'membership_events_kind_occurred_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS membership_events_kind_occurred_at_index ON membership_events (kind, occurred_at)',
        // moderation_events: was moderation_events_tenant_id_type_occurred_at_index
        'moderation_events_type_occurred_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_events_type_occurred_at_index ON moderation_events (type, occurred_at)',
        // interactions: was idx_interactions_tenant (tenant_id, occurred_at)
        'interactions_occurred_at_index' => 'CREATE INDEX CONCURRENTLY IF NOT EXISTS interactions_occurred_at_index ON interactions (occurred_at)',
    ];

    public function up(): void
    {
        foreach ($this->indexes as $statement) {
            DB::statement($statement);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->indexes) as $index) {
            DB::statement(sprintf('DROP INDEX CONCURRENTLY IF EXISTS %s', $index));
        }
    }
};
