<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squad_membership_events', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('squad_id')->constrained('squads')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('from_role', 30)->nullable();
            $table->string('to_role', 30)->nullable();
            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['squad_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_membership_events');
    }
};
