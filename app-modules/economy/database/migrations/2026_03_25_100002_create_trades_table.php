<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->nullOnDelete();
            $table->foreignUuid('initiator_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignUuid('receiver_character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('status', 50)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['initiator_character_id', 'status']);
            $table->index(['receiver_character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
