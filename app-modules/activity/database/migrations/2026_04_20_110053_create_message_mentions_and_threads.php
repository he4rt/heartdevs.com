<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_mentions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->foreignUuid('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignUuid('mentioned_identity_id')->nullable()->constrained('external_identities');
            $table->string('mentioned_provider_account_id');
            $table->string('mentioned_username')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(
                ['message_id', 'mentioned_provider_account_id'],
                'message_mentions_message_provider_unique',
            );
            $table->index(
                ['tenant_id', 'mentioned_identity_id'],
                'message_mentions_tenant_identity_idx',
            );
            $table->index(
                ['tenant_id', 'mentioned_provider_account_id'],
                'message_mentions_tenant_provider_idx',
            );
        });

        Schema::create('message_threads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->foreignUuid('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('provider_thread_id');
            $table->string('name')->nullable();
            $table->boolean('archived')->nullable();
            $table->unsignedInteger('auto_archive_duration')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'provider_thread_id'],
                'message_threads_tenant_provider_unique',
            );
            $table->index('message_id', 'message_threads_message_idx');
        });

        Schema::table('messages', function (Blueprint $table): void {
            if (!Schema::hasColumn('messages', 'reply_to_message_id')) {
                $table->foreignUuid('reply_to_message_id')
                    ->nullable()
                    ->after('reply_to_provider_message_id')
                    ->constrained('messages')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            if (Schema::hasColumn('messages', 'reply_to_message_id')) {
                $table->dropForeign(['reply_to_message_id']);
                $table->dropColumn('reply_to_message_id');
            }
        });

        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('message_mentions');
    }
};
