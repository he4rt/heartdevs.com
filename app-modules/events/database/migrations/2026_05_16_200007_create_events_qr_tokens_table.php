<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_qr_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('enrollment_id')->unique()->constrained('events_enrollments')->cascadeOnDelete();
            $table->string('token', 64);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();

            $table->unique('token', 'idx_qr_tokens_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_qr_tokens');
    }
};
