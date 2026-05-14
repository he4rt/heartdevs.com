<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('networking_connections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('initiator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->text('message')->nullable();
            $table->unique(['event_id', 'initiator_user_id', 'target_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('networking_connections');
    }
};
