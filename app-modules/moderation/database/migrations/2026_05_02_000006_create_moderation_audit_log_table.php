<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_audit_log', static function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 50);
            $table->uuid('actor_id')->nullable();
            $table->string('actor_type', 20)->nullable();
            $table->uuid('case_id')->nullable();
            $table->jsonb('details');
            $table->string('platform', 20)->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at'], 'idx_audit_tenant_date');
            $table->index(['case_id'], 'idx_audit_case');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_audit_log');
    }
};
