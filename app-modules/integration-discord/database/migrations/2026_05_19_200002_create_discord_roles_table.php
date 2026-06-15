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
        Schema::create('discord_roles', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discord_guild_id')->constrained('discord_guilds')->cascadeOnDelete();
            $table->string('discord_role_id')->unique();
            $table->string('name');
            $table->integer('color')->default(0);
            $table->smallInteger('position')->default(0);
            $table->bigInteger('permissions')->default(0);
            $table->boolean('is_hoisted')->default(false);
            $table->boolean('is_mentionable')->default(false);
            $table->boolean('is_managed')->default(false);
            $table->string('icon')->nullable();
            $table->timestampsTz();

            $table->index('discord_guild_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_roles');
    }
};
