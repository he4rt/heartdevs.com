<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('trade_id')->constrained('trades')->cascadeOnDelete();
            $table->foreignUuid('character_item_id')->constrained('character_items');
            $table->string('direction', 20);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_items');
    }
};
