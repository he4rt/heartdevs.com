<?php

declare(strict_types=1);

use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Enums\DelasTriggeredBy;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delas_transitions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('request_id')->nullable()->constrained('delas_tag_requests')->cascadeOnDelete();
            $table->foreignUuid('block_id')->nullable()->constrained('delas_requester_blocks')->cascadeOnDelete();
            $table->string('action', 20)->comment(DelasAction::stringifyCases());
            $table->string('from_status', 20)->nullable()->comment(DelasRequestStatus::stringifyCases());
            $table->string('to_status', 20)->nullable()->comment(DelasRequestStatus::stringifyCases());
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('triggered_by', 20)->comment(DelasTriggeredBy::stringifyCases());
            $table->string('reason', 500)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['created_at'], 'idx_delas_transitions_time');
            $table->index(['user_id', 'created_at'], 'idx_delas_transitions_user_time');
            $table->index(['actor_id', 'created_at'], 'idx_delas_transitions_actor_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delas_transitions');
    }
};
