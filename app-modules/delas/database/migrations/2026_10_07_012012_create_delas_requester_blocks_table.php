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
        Schema::create('delas_requester_blocks', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->comment('Uso interno: a pessoa bloqueada não vê.');
            $table->timestampTz('blocked_at');
            $table->foreignUuid('lifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('lifted_at')->nullable();
            $table->string('lift_reason', 500)->nullable()->comment('Obrigatório ao desbloquear; nulo enquanto o bloqueio está ativo.');
            $table->timestampsTz();

            $table->index(['lifted_at', 'blocked_at'], 'idx_delas_requester_blocks_active');
        });

        // No máximo um bloqueio ativo por pessoa.
        DB::statement('CREATE UNIQUE INDEX uq_delas_requester_blocks_active_user ON delas_requester_blocks (user_id) WHERE lifted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('delas_requester_blocks');
    }
};
