<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_enrollment_transitions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('enrollment_id')->constrained('events_enrollments')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable()->comment(EnrollmentStatus::stringifyCases());
            $table->string('to_status', 20)->comment(EnrollmentStatus::stringifyCases());
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('triggered_by', 20)->comment(TriggeredBy::stringifyCases());
            $table->string('reason', 500)->nullable();
            // contexto adicional para audit trail: notas do admin, job ID, código de razão, etc.
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['enrollment_id', 'created_at'], 'idx_transitions_enrollment_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_enrollment_transitions');
    }
};
