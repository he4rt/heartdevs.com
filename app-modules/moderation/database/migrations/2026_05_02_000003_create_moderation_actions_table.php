<?php

declare(strict_types=1);

use He4rt\Moderation\Enums\ActionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_actions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('moderation_cases')->cascadeOnDelete();
            $table->foreignUuid('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 30)->comment(ActionType::stringifyCases());
            $table->jsonb('target_platforms');
            $table->string('duration', 20)->nullable();
            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->jsonb('execution_results')->nullable();
            $table->boolean('automated')->default(value: false);
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['case_id'], 'idx_actions_case');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_actions');
    }
};
