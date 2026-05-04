<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('type', 20);
            $table->string('platform', 20)->nullable();
            $table->text('pattern');
            $table->string('violation_type', 30);
            $table->string('severity', 20);
            $table->string('action_on_match', 30);
            $table->boolean('is_active')->default(true);
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['is_active', 'tenant_id'], 'idx_rules_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_rules');
    }
};
