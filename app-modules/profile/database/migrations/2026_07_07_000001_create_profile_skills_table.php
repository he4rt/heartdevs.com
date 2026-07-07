<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_skills', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->foreignUuid('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->string('proficiency', 30);
            $table->smallInteger('years_experience')->nullable();
            $table->timestampsTz();

            $table->unique(['profile_id', 'skill_id']);
            $table->index(['skill_id', 'proficiency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_skills');
    }
};
