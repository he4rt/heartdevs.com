<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('networking_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('enrollment_id')->constrained('event_enrollments')->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->jsonb('skills')->nullable();
            $table->jsonb('looking_for')->nullable();
            $table->jsonb('interests')->nullable();
            $table->boolean('contact_visible')->default(false);
            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('networking_profiles');
    }
};
