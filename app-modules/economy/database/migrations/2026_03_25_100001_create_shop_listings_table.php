<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_listings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->nullOnDelete();
            $table->foreignUuid('item_id')->constrained('items');
            $table->integer('price');
            $table->integer('stock')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'active']);
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_listings');
    }
};
