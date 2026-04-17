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
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
            });
        }

        if (!Schema::hasColumn('voice_messages', 'tenant_id')) {
            Schema::table('voice_messages', function (Blueprint $table): void {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
            });
        }

        Schema::table('messages', function (Blueprint $table): void {
            $table->jsonb('metadata')->nullable()->after('obtained_experience');
            $table->unsignedInteger('reactions_count')->default(0)->after('metadata');
            $table->unsignedInteger('reactions_total')->default(0)->after('reactions_count');

            $table->index(['tenant_id', 'sent_at'], 'messages_tenant_sent_at_idx');
            $table->index(['tenant_id', 'channel_id', 'sent_at'], 'messages_tenant_channel_sent_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_tenant_sent_at_idx');
            $table->dropIndex('messages_tenant_channel_sent_at_idx');
            $table->dropColumn(['metadata', 'reactions_count', 'reactions_total']);
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
