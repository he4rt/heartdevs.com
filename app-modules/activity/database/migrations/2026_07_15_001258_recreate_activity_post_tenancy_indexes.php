<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $statements = [
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_sent_at_index ON messages (sent_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_channel_id_sent_at_index ON messages (channel_id, sent_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS messages_kind_index ON messages (kind)',
            "CREATE INDEX CONCURRENTLY IF NOT EXISTS voice_messages_joined_occurred_at_index ON voice_messages (occurred_at) WHERE state = 'joined'",
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_timeline_created_at_index ON activity_timeline (created_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_timeline_feed_composite_index ON activity_timeline (parent_id, is_ignored, created_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS activity_reactions_emoji_key_created_at_index ON activity_reactions (emoji_key, created_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_mentions_mentioned_identity_id_index ON message_mentions (mentioned_identity_id)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_mentions_mentioned_provider_account_id_index ON message_mentions (mentioned_provider_account_id)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_embeds_source_domain_index ON message_embeds (source_domain)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS message_embeds_kind_index ON message_embeds (kind)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS membership_events_kind_occurred_at_index ON membership_events (kind, occurred_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS moderation_events_type_occurred_at_index ON moderation_events (type, occurred_at)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS interactions_occurred_at_index ON interactions (occurred_at)',
        ];

        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }
};
