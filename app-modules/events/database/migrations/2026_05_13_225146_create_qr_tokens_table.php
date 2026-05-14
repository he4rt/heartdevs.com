<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('enrollment_id')->constrained('event_enrollments')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_tokens');
    }
};
