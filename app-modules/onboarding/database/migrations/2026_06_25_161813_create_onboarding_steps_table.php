<?php

declare(strict_types=1);

use He4rt\Onboarding\Enums\OnboardingStepStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_steps', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('onboarding_id')->constrained('onboardings')->cascadeOnDelete();
            $table->string('step_key');
            $table->string('status')->comment(OnboardingStepStatus::stringifyCases());
            $table->jsonb('data')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['onboarding_id', 'step_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_steps');
    }
};
