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
        Schema::create('squad_applications', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('squad_id')->constrained('squads')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->text('message')->nullable();
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();

            $table->index(['squad_id', 'status']);
        });

        // At most one open application per person per squad. Decided rows (approved
        // or rejected) fall out of the index, so re-applying after a rejection works.
        DB::statement(
            "CREATE UNIQUE INDEX squad_applications_pending_unique ON squad_applications (squad_id, user_id) WHERE status = 'pending'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_applications');
    }
};
