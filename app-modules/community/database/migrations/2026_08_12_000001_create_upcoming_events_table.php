<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upcoming_events', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 30);
            $table->unsignedTinyInteger('week_day')->nullable();
            $table->time('time')->nullable();
            $table->timestampTz('event_at')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('external_url', 255)->nullable();
            $table->boolean('is_active')->default(value: true);
            $table->boolean('skip_next_occurrence')->default(value: false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upcoming_events');
    }
};
