<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('messages', 'tenant_id')) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->foreignUuid('tenant_id')->nullable()->after('id')->constrained('tenants');
            });
        }

        if (!Schema::hasColumn('voice_messages', 'tenant_id')) {
            Schema::table('voice_messages', function (Blueprint $table): void {
                $table->foreignUuid('tenant_id')->nullable()->after('id')->constrained('tenants');
            });
        }

        Schema::table('messages', function (Blueprint $table): void {
            $table->jsonb('metadata')->nullable()->after('obtained_experience');
            $table->unsignedInteger('reactions_count')->default(0)->after('metadata');
            $table->unsignedInteger('reactions_total')->default(0)->after('reactions_count');

            $table->string('kind')->nullable()->after('reactions_total');
            $table->smallInteger('raw_message_type')->nullable()->after('kind');
            $table->string('source_kind')->nullable()->after('raw_message_type');
            $table->boolean('is_pinned')->default(false)->after('source_kind');
            $table->boolean('mentions_everyone')->default(false)->after('is_pinned');
            $table->smallInteger('mention_role_count')->default(0)->after('mentions_everyone');
            $table->timestamp('edited_at')->nullable()->after('mention_role_count');
            $table->string('reply_to_provider_message_id')->nullable()->after('edited_at');

            $table->index(['tenant_id', 'sent_at'], 'messages_tenant_sent_at_idx');
            $table->index(['tenant_id', 'channel_id', 'sent_at'], 'messages_tenant_channel_sent_at_idx');
            $table->index(['tenant_id', 'kind'], 'messages_tenant_kind_idx');
            $table->index('reply_to_provider_message_id', 'messages_reply_to_provider_message_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_tenant_sent_at_idx');
            $table->dropIndex('messages_tenant_channel_sent_at_idx');
            $table->dropIndex('messages_tenant_kind_idx');
            $table->dropIndex('messages_reply_to_provider_message_id_idx');
            $table->dropColumn([
                'metadata',
                'reactions_count',
                'reactions_total',
                'kind',
                'raw_message_type',
                'source_kind',
                'is_pinned',
                'mentions_everyone',
                'mention_role_count',
                'edited_at',
                'reply_to_provider_message_id',
            ]);
        });

        if (Schema::hasColumn('messages', 'tenant_id')) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasColumn('voice_messages', 'tenant_id')) {
            Schema::table('voice_messages', function (Blueprint $table): void {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
