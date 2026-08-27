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
        Schema::table('moderation_events', static function (Blueprint $table): void {
            $table->string('provider_message_id')->nullable()->after('source_message_id');
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX moderation_events_tenant_provider_message_id_unique
            ON moderation_events (tenant_id, provider_message_id)
            WHERE provider_message_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS moderation_events_tenant_provider_message_id_unique');

        Schema::table('moderation_events', static function (Blueprint $table): void {
            $table->dropColumn('provider_message_id');
        });
    }
};
