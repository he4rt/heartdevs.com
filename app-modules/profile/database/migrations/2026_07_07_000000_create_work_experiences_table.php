<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_experiences', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('position');
            $table->text('description');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_currently_working_here')->default(value: false);
            $table->timestampsTz();

            $table->index(['profile_id', 'is_currently_working_here', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_experiences');
    }
};
