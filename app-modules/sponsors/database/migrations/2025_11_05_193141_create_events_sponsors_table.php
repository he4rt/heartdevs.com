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
        Schema::create('events_sponsors', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('sponsor_id')->constrained('sponsors')->onDelete('cascade');
            $table->string('level'); // Platinum, Gold, Silver, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sponsors');
    }
};
