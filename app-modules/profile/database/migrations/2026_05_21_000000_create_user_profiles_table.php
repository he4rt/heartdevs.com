<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nickname')->nullable();
            $table->date('birthdate')->nullable();
            $table->text('about')->nullable();
            $table->string('headline')->nullable();
            $table->string('seniority_level', 30)->nullable();
            $table->smallInteger('years_experience')->nullable();
            $table->jsonb('social_links')->nullable();
            $table->boolean('available_for_proposals')->default(false);
            $table->string('start_availability', 30)->nullable();
            $table->timestampsTz();

            $table->unique(['user_id', 'tenant_id']);
        });

        DB::statement('CREATE INDEX user_profiles_available_for_proposals_index ON user_profiles (tenant_id, user_id) WHERE available_for_proposals = true');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
