<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items');
            $table->foreignId('tenant_id')->constrained('tenants')->nullOnDelete();
            $table->string('acquired_via', 50);
            $table->timestamp('acquired_at');
            $table->timestamps();

            $table->unique(['character_id', 'item_id']);
            $table->index('character_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_items');
    }
};
