<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_messages', function (Blueprint $table): void {
            $table->string('provider_message_id')->nullable()->after('external_identity_id');
            $table->timestampTz('occurred_at')->nullable()->after('state');
        });

        // Partial unique index — only enforced when we have a provider_message_id
        // to dedupe on. Rows without it (legacy pre-ETL) stay NULL.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX voice_messages_tenant_provider_message_id_unique
            ON voice_messages (tenant_id, provider_message_id)
            WHERE provider_message_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS voice_messages_tenant_provider_message_id_unique');

        Schema::table('voice_messages', function (Blueprint $table): void {
            $table->dropColumn(['provider_message_id', 'occurred_at']);
        });
    }
};
