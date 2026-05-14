<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_in_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('code');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->integer('max_uses')->nullable();
            $table->unique(['event_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_in_codes');
    }
};
