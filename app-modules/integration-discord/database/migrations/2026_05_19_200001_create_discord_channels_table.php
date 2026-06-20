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
        Schema::create('discord_channels', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discord_guild_id')->constrained('discord_guilds')->cascadeOnDelete();
            $table->string('discord_channel_id')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('discord_channels')->nullOnDelete();
            $table->string('name');
            $table->smallInteger('type');
            $table->text('topic')->nullable();
            $table->smallInteger('position')->default(0);
            $table->boolean('nsfw')->default(value: false);
            $table->integer('bitrate')->nullable();
            $table->integer('user_limit')->nullable();
            $table->timestampsTz();

            $table->index('discord_guild_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_channels');
    }
};
