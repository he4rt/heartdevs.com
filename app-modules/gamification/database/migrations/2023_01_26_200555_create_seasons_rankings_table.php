<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons_rankings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('season_id');
            $table->foreignUuid('character_id')->constrained('characters')->cascadeOnDelete();
            $table->integer('ranking_position');
            $table->integer('level');
            $table->integer('experience');
            $table->integer('messages_count');
            $table->integer('badges_count');
            $table->integer('meetings_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons_rankings');
    }
};
