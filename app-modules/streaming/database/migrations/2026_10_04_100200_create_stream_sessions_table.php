<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('streamer_id')->constrained('streamers')->restrictOnDelete();
            $table->foreignUuid('external_identity_id')->constrained('external_identities')->restrictOnDelete();
            $table->string('platform_stream_id');
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();

            $table->unique(['external_identity_id', 'platform_stream_id']);
            $table->index(['streamer_id', 'started_at']);
        });

        DB::statement('CREATE UNIQUE INDEX stream_sessions_one_open_per_identity ON stream_sessions (external_identity_id) WHERE ended_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_sessions');
    }
};
