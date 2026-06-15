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
        Schema::create('discord_members', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discord_guild_id')->constrained('discord_guilds')->cascadeOnDelete();
            $table->string('discord_user_id');
            $table->foreignUuid('external_identity_id')->nullable()->constrained('external_identities')->nullOnDelete();
            $table->string('username');
            $table->string('global_name')->nullable();
            $table->string('avatar')->nullable();
            $table->string('nickname')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_pending')->default(false);
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('premium_since')->nullable();
            $table->timestampTz('communication_disabled_until')->nullable();
            $table->timestampTz('left_at')->nullable();
            $table->timestampsTz();

            $table->unique(['discord_guild_id', 'discord_user_id']);
            $table->index('discord_user_id');
            $table->index('external_identity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_members');
    }
};
