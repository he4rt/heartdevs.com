<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_enrollment_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->unique('event_id');
            $table->string('enrollment_method');
            $table->string('check_in_method');
            $table->integer('capacity');
            $table->boolean('has_waitlist')->default(false);
            $table->integer('cancellation_deadline_h');
            $table->jsonb('application_form')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->integer('xp_reward_rsvp')->default(0);
            $table->integer('xp_reward_checkin')->default(0);
            $table->integer('xp_reward_referral')->default(0);
            $table->decimal('venue_lat', 11, 7)->nullable();
            $table->decimal('venue_lng', 12, 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_enrollment_policies');
    }
};
