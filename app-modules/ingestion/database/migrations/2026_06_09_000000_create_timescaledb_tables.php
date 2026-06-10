<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'timescaledb';

    public function up(): void
    {
        DB::connection('timescaledb')->statement('CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE;');

        Schema::connection('timescaledb')->dropIfExists('voice_messages');
        Schema::connection('timescaledb')->dropIfExists('messages');
        Schema::connection('timescaledb')->dropIfExists('raw_payloads');

        Schema::connection('timescaledb')->create('raw_payloads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('event_type');
            $table->jsonb('payload');
            $table->timestampsTz();
        });

        Schema::connection('timescaledb')->create('messages', function (Blueprint $table): void {
            $table->uuid('id');
            $table->uuid('tenant_id')->nullable();
            $table->string('external_identity_id');
            $table->string('provider_message_id')->nullable();
            $table->string('channel_id')->nullable();
            $table->text('content');
            $table->integer('obtained_experience')->default(0);
            $table->jsonb('metadata')->nullable();
            $table->unsignedInteger('reactions_count')->default(0);
            $table->unsignedInteger('reactions_total')->default(0);
            $table->string('kind')->nullable();
            $table->smallInteger('raw_message_type')->nullable();
            $table->string('source_kind')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('mentions_everyone')->default(false);
            $table->smallInteger('mention_role_count')->default(0);
            $table->timestampTz('edited_at')->nullable();
            $table->string('reply_to_provider_message_id')->nullable();

            // Time column for partitioning
            $table->timestampTz('sent_at');
            $table->timestampsTz();

            // Composite primary key (required for Timescale hypertable)
            $table->primary(['id', 'sent_at']);
        });

        DB::connection('timescaledb')->statement("SELECT create_hypertable('messages', 'sent_at');");

        Schema::connection('timescaledb')->create('voice_messages', function (Blueprint $table): void {
            $table->uuid('id');
            $table->uuid('tenant_id')->nullable();
            $table->string('external_identity_id');
            $table->string('channel_name');
            $table->string('channel_id')->nullable();
            $table->string('state');
            $table->integer('obtained_experience')->default(0);
            $table->string('provider_message_id')->nullable();

            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->primary(['id', 'occurred_at']);
        });

        DB::connection('timescaledb')->statement("SELECT create_hypertable('voice_messages', 'occurred_at');");
    }

    public function down(): void
    {
        Schema::connection('timescaledb')->dropIfExists('voice_messages');
        Schema::connection('timescaledb')->dropIfExists('messages');
        Schema::connection('timescaledb')->dropIfExists('raw_payloads');
    }
};
