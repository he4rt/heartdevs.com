<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_check_ins', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('enrollment_id')->constrained('event_enrollments')->cascadeOnDelete();
            $table->string('method');
            $table->jsonb('payload')->nullable();
            $table->foreignUuid('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_check_ins');
    }
};
