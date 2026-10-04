<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streamer_sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('streamer_id')->constrained('streamers')->restrictOnDelete();
            $table->foreignUuid('external_identity_id')->constrained('external_identities')->restrictOnDelete();
            $table->boolean('enabled')->default(value: true);
            $table->string('chat_reader')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['streamer_id', 'external_identity_id']);
            $table->index('external_identity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streamer_sources');
    }
};
