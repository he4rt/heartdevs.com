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
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('provider_id')->constrained('providers');
                $table->string('provider_message_id')->nullable();
                $table->integer('season_id');
                $table->string('channel_id')->nullable();
                $table->text('content');
                $table->integer('obtained_experience');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
