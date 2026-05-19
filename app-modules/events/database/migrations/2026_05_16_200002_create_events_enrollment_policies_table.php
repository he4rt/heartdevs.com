<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_enrollment_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->string('enrollment_method', 20);
            $table->string('check_in_method', 20);
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('waitlist_enabled')->default(false);
            $table->string('attendance_requirement', 20)->default('all_days');
            // número mínimo de dias que o participante deve comparecer para receber XP/certificado (usado quando attendance_requirement = 'minimum_days')
            $table->unsignedSmallInteger('minimum_days')->nullable();
            $table->unsignedSmallInteger('cancellation_deadline_hours')->nullable();
            $table->unsignedInteger('xp_on_confirmed')->default(0);
            $table->unsignedInteger('xp_on_checked_in')->default(0);
            $table->unsignedInteger('xp_on_attended')->default(0);
            $table->jsonb('application_schema')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_enrollment_policies');
    }
};
