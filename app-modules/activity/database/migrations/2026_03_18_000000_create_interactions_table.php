<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('character_id')->constrained('characters');
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->string('type');
            $table->string('provider');
            $table->string('value_tier');
            $table->integer('coins_min');
            $table->integer('coins_max');
            $table->integer('coins_awarded')->nullable();
            $table->integer('xp_awarded')->nullable();
            $table->string('status')->default('pending');
            $table->nullableUuidMorphs('source');
            $table->string('external_ref')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();

            $table->index(['character_id', 'type', 'created_at'], 'idx_interactions_character_type');
            $table->index(['status', 'value_tier'], 'idx_interactions_status_tier');
            $table->index(['tenant_id', 'occurred_at'], 'idx_interactions_tenant');
            $table->unique(['tenant_id', 'provider', 'external_ref'], 'uniq_interactions_tenant_provider_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
