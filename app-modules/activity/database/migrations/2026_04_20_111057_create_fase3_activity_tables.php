<?php

declare(strict_types=1);

use He4rt\Activity\Message\Enums\MembershipEventKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->foreignUuid('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('provider_attachment_id')->nullable();
            $table->text('url');
            $table->text('filename')->nullable();
            $table->text('content_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestampsTz();

            $table->index('message_id', 'message_attachments_message_idx');
        });

        Schema::create('message_embeds', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->foreignUuid('message_id')->constrained('messages')->cascadeOnDelete();
            $table->text('url')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->string('source_domain')->nullable();
            $table->string('kind')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->jsonb('raw')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestampsTz();

            $table->index('message_id', 'message_embeds_message_idx');
            $table->index(['tenant_id', 'source_domain'], 'message_embeds_tenant_domain_idx');
            $table->index(['tenant_id', 'kind'], 'message_embeds_tenant_kind_idx');
        });

        Schema::create('membership_events', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->foreignUuid('external_identity_id')->constrained('external_identities');
            $table->string('kind')->comment(MembershipEventKind::stringifyCases());
            $table->timestampTz('occurred_at');
            $table->string('provider_message_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'kind', 'occurred_at'], 'membership_events_tenant_kind_time_idx');
            $table->index(['external_identity_id', 'kind'], 'membership_events_identity_kind_idx');
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX membership_events_tenant_provider_unique
            ON membership_events (tenant_id, provider_message_id)
            WHERE provider_message_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_events');
        Schema::dropIfExists('message_embeds');
        Schema::dropIfExists('message_attachments');
    }
};
