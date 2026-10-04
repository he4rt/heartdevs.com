<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('streamer_id')->constrained('streamers')->restrictOnDelete();
            $table->foreignUuid('external_identity_id')->constrained('external_identities')->restrictOnDelete();
            $table->foreignUuid('stream_session_id')->nullable()->constrained('stream_sessions')->restrictOnDelete();
            $table->string('type');
            $table->string('actor_platform_id')->nullable();
            $table->string('actor_login')->nullable();
            $table->string('actor_display_name')->nullable();
            $table->jsonb('details')->nullable();
            $table->string('source_event_id');
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->nullable();

            $table->unique(['external_identity_id', 'source_event_id']);
            $table->index(['streamer_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_events');
    }
};
