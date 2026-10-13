<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_timeline_likes', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('timeline_id')->constrained('activity_timeline')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestampsTz();

            $table->unique(['timeline_id', 'user_id'], 'idx_timeline_likes_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_timeline_likes');
    }
};
