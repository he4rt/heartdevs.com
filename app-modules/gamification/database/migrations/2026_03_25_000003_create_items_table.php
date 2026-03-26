<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->nullOnDelete();
            $table->foreignId('slot_id')->constrained('item_slots');
            $table->foreignId('rarity_id')->constrained('item_rarities');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_tradeable')->default(true);
            $table->boolean('is_purchasable')->default(false);
            $table->integer('price')->nullable();
            $table->decimal('drop_rate', 5, 4)->nullable();
            $table->integer('level_required')->default(0);
            $table->boolean('active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index('slot_id');
            $table->index('rarity_id');
            $table->index(['tenant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
