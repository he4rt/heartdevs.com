<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->foreignUuid('external_identity_id')->nullable()->constrained('external_identities');
            $table->foreignUuid('moderator_identity_id')->nullable()->constrained('external_identities');
            $table->string('type');
            $table->text('reason')->nullable();
            $table->foreignUuid('source_identity_id')->nullable()->constrained('external_identities');
            $table->uuid('source_message_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->foreign('source_message_id')->references('id')->on('messages');
            $table->index(['tenant_id', 'type', 'occurred_at']);
            $table->index(['external_identity_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_events');
    }
};
