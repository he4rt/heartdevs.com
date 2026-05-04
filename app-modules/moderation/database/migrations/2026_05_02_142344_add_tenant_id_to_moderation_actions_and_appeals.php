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
        Schema::table('moderation_actions', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('automated')->constrained('tenants')->nullOnDelete();
        });

        Schema::table('moderation_appeals', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('sla_deadline')->constrained('tenants')->nullOnDelete();
        });

        DB::statement('
            UPDATE moderation_actions
            SET tenant_id = moderation_cases.tenant_id
            FROM moderation_cases
            WHERE moderation_actions.case_id = moderation_cases.id
        ');

        DB::statement('
            UPDATE moderation_appeals
            SET tenant_id = moderation_cases.tenant_id
            FROM moderation_actions
            JOIN moderation_cases ON moderation_actions.case_id = moderation_cases.id
            WHERE moderation_appeals.action_id = moderation_actions.id
        ');
    }

    public function down(): void
    {
        Schema::table('moderation_actions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('moderation_appeals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
