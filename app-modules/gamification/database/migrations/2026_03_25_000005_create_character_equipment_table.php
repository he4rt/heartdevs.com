<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_equipment', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('item_slots');
            $table->foreignUuid('character_item_id')->constrained('character_items')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->nullOnDelete();
            $table->timestamp('equipped_at');
            $table->timestamps();

            $table->unique(['character_id', 'slot_id']);
            $table->index('character_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_equipment');
    }
};
