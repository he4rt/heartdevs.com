<?php

declare(strict_types=1);

use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboardings', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->comment(OnboardingType::stringifyCases());
            $table->string('status')->comment(OnboardingStatus::stringifyCases());
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('paused_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboardings');
    }
};
