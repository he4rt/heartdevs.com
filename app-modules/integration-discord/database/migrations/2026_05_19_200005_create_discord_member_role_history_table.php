<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_member_role_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discord_member_id')->constrained('discord_members')->cascadeOnDelete();
            $table->foreignId('discord_role_id')->constrained('discord_roles')->cascadeOnDelete();
            $table->string('action');
            $table->timestamp('occurred_at');
            $table->foreignId('source_event_log_id')->nullable()->constrained('discord_event_logs')->nullOnDelete();
            $table->timestamps();

            $table->index('discord_member_id');
            $table->index('discord_role_id');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_member_role_history');
    }
};
