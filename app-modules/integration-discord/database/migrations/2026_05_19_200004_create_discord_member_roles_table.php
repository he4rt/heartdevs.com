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
        Schema::create('discord_member_roles', static function (Blueprint $table): void {
            $table->foreignId('discord_member_id')->constrained('discord_members')->cascadeOnDelete();
            $table->foreignId('discord_role_id')->constrained('discord_roles')->cascadeOnDelete();
            $table->timestampTz('assigned_at')->nullable();
            $table->timestampsTz();

            $table->primary(['discord_member_id', 'discord_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_member_roles');
    }
};
