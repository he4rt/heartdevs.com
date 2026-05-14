<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_referral_clicks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('referral_id')->constrained('event_referrals')->cascadeOnDelete();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer_url')->nullable();
            $table->timestamp('clicked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_referral_clicks');
    }
};
