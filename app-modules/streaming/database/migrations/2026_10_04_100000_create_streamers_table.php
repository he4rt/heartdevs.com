<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\StreamerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streamers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('status')->default(StreamerStatus::Active->value);
            $table->text('overlay_token');
            $table->string('overlay_token_hash', 64)->unique();
            $table->jsonb('settings')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streamers');
    }
};
