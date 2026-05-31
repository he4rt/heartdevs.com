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
        Schema::create('discord_event_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type')->index();
            $table->string('guild_id')->nullable()->index();
            $table->string('user_id')->nullable();
            $table->string('channel_id')->nullable();
            $table->jsonb('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_event_logs');
    }
};
