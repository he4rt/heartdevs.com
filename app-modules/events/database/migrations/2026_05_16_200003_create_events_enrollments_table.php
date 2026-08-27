<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_enrollments', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)
                ->comment(EnrollmentStatus::stringifyCases())
                ->default(EnrollmentStatus::Pending);
            $table->boolean('is_public')->default(value: true);
            $table->unsignedInteger('waitlist_position')->nullable();
            $table->jsonb('application_data')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestampTz('enrolled_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('checked_in_at')->nullable();
            $table->timestampTz('attended_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->unique(['event_id', 'user_id'], 'idx_enrollments_unique');
            $table->index(['event_id', 'status'], 'idx_enrollments_event_status');
            $table->index(['status', 'waitlist_position'], 'idx_enrollments_waitlist');
            $table->index(['user_id', 'status'], 'idx_enrollments_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_enrollments');
    }
};
