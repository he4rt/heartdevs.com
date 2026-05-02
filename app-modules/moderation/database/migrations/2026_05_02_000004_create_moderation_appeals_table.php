<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_appeals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('action_id')->constrained('moderation_actions')->cascadeOnDelete();
            $table->foreignUuid('appellant_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason_category', 50);
            $table->text('reason_text')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('sla_deadline');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['status', 'sla_deadline'], 'idx_appeals_sla');
            $table->index(['action_id'], 'idx_appeals_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_appeals');
    }
};
