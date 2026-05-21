<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twitch_event_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type')->index();
            $table->string('broadcaster_user_id')->nullable()->index();
            $table->string('user_id')->nullable();
            $table->string('twitch_message_id')->nullable()->unique();
            $table->jsonb('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twitch_event_logs');
    }
};
