<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delas_transitions', static function (Blueprint $table): void {
            $table->foreignUuid('corrects_id')
                ->nullable()
                ->after('block_id')
                ->comment('Linha do histórico cujo motivo esta linha corrige; a original nunca é alterada.')
                ->constrained('delas_transitions')
                ->cascadeOnDelete();

            $table->index(['corrects_id', 'created_at'], 'idx_delas_transitions_corrects');
        });
    }

    public function down(): void
    {
        Schema::table('delas_transitions', static function (Blueprint $table): void {
            $table->dropIndex('idx_delas_transitions_corrects');
            $table->dropConstrainedForeignId('corrects_id');
        });
    }
};
