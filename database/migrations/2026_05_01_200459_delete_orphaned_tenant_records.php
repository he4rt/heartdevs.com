<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!app()->isProduction()) {
            return;
        }

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

        // Guarded so a replay after multi-tenancy removal is a no-op instead of
        // referencing dropped `tenant_id` columns / the dropped `tenants` table.
        DB::transaction(static function () use ($tables, $tenantId): void {
            foreach ($tables as $table) {
                if (!Schema::hasColumn($table, 'tenant_id')) {
                    continue;
                }

                DB::table($table)->where('tenant_id', $tenantId)->delete();
            }

            if (Schema::hasTable('tenants')) {
                DB::table('tenants')->where('id', $tenantId)->delete();
            }
        });
    }
};
