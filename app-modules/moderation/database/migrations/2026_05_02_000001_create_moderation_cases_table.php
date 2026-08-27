<?php

declare(strict_types=1);

use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_cases', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('content_type', 50);
            $table->string('content_id', 255);
            $table->jsonb('content_snapshot')->nullable();
            $table->string('source_platform', 20)->comment(Platform::stringifyCases());
            $table->string('source', 20)->comment(CaseSource::stringifyCases());
            $table->string('status', 20)->default('pending')->comment(CaseStatus::stringifyCases());
            $table->integer('priority')->default(50);
            $table->string('severity', 20)->nullable()->comment(Severity::stringifyCases());
            $table->string('violation_type', 30)->nullable()->comment(ViolationType::stringifyCases());
            $table->jsonb('ai_scores')->nullable();
            $table->string('classifier_version', 50)->nullable();
            $table->string('suggested_action', 30)->nullable()->comment(ActionType::stringifyCases());
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('assigned_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['tenant_id', 'status', 'priority', 'created_at'], 'idx_cases_queue');
            $table->index(['author_id', 'created_at'], 'idx_cases_author');
            $table->index(['source_platform', 'created_at'], 'idx_cases_platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_cases');
    }
};
