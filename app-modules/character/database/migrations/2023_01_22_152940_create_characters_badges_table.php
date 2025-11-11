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
        if (! Schema::hasTable('characters_badges')) {
            Schema::create('characters_badges', function (Blueprint $table): void {
                $table->foreignUuid('character_id')->constrained('characters')->cascadeOnDelete();
                $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
                $table->timestamp('claimed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters_badges');
    }
};
