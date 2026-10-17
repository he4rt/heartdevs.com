<?php

declare(strict_types=1);

use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delas_tag_requests', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->comment(DelasRequestStatus::stringifyCases());
            $table->timestampTz('requested_at');
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->string('decision_reason', 500)->nullable()->comment('Uso interno: a solicitante não vê.');
            $table->timestampsTz();

            $table->index(['status', 'requested_at'], 'idx_delas_tag_requests_queue');
            $table->index(['user_id', 'decided_at'], 'idx_delas_tag_requests_user_decided');
        });

        // No máximo uma solicitação ativa (pendente ou aprovada) por pessoa.
        DB::statement(sprintf(
            "CREATE UNIQUE INDEX uq_delas_tag_requests_active_user ON delas_tag_requests (user_id) WHERE status IN ('%s')",
            implode("', '", DelasRequestStatus::activeValues()),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('delas_tag_requests');
    }
};
