<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_check_ins', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('enrollment_id')->constrained('events_enrollments')->cascadeOnDelete();
            $table->date('check_in_date');
            $table->string('method', 20);
            $table->jsonb('payload')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['enrollment_id', 'check_in_date'], 'idx_check_ins_unique_per_day');
            $table->index('check_in_date', 'idx_check_ins_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_check_ins');
    }
};
