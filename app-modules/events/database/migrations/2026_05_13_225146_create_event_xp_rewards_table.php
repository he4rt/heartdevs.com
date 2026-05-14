<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_xp_rewards', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained('event_enrollments')->cascadeOnDelete();
            $table->string('reason');
            $table->integer('xp_amount');
            $table->string('source_type');
            $table->uuid('source_id');
            $table->unique(['enrollment_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_xp_rewards');
    }
};
