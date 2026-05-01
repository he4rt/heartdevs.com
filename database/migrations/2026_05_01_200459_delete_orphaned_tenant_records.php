<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenantId = 2;

        $tables = [
            'events_agenda',
            'activity_reactions',
            'interactions',
            'membership_events',
            'message_attachments',
            'message_embeds',
            'message_mentions',
            'message_threads',
            'moderation_events',
            'characters_badges',
            'seasons_rankings',
            'feedback_reviews',
            'feedbacks',
            'events_talks',
            'voice_messages',
            'messages',
            'external_identities',
            'meetings',
            'sponsors',
            'events',
            'characters',
            'badges',
            'seasons',
            'tenant_users',
        ];

        DB::transaction(function () use ($tables, $tenantId): void {
            foreach ($tables as $table) {
                DB::table($table)->where('tenant_id', $tenantId)->delete();
            }

            DB::table('tenants')->where('id', $tenantId)->delete();
        });
    }
};
