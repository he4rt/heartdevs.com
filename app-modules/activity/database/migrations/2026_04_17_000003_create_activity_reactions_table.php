<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_reactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->uuidMorphs('reactable');

            $table->string('emoji_key', 128);
            $table->string('emoji_id')->nullable();
            $table->string('emoji_name');

            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('count_burst')->default(0);
            $table->unsignedInteger('count_normal')->default(0);
            $table->timestampsTz();

            $table->unique(
                ['reactable_type', 'reactable_id', 'emoji_key'],
                'activity_reactions_reactable_emoji_unique'
            );
            $table->index(
                ['tenant_id', 'emoji_key', 'created_at'],
                'activity_reactions_tenant_emoji_time_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reactions');
    }
};
