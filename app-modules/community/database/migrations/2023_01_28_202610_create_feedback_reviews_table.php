<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedback_reviews', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('feedback_id')->constrained('feedbacks')->cascadeOnDelete();
            $table->foreignUuid('staff_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->text('reason')->nullable();
            $table->timestampTz('received_at');
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_reviews');
    }
};
