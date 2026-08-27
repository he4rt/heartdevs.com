<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Enums\CheckInMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_check_ins', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('enrollment_id')->constrained('events_enrollments')->cascadeOnDelete();
            $table->date('event_date');
            $table->string('method', 20)->comment(CheckInMethod::stringifyCases());
            $table->jsonb('payload')->nullable();
            $table->timestampsTz();
            $table->unique(['enrollment_id', 'event_date'], 'idx_check_ins_unique_per_day');
            $table->index('event_date', 'idx_check_ins_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_check_ins');
    }
};
