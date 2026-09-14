<?php

declare(strict_types=1);

use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug', 120);
            $table->string('title', 200);
            $table->longText('description')->nullable();
            $table->string('event_type', 20)->comment(EventType::stringifyCases());
            $table->string('location')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status', 20)
                ->comment(EventStatus::stringifyCases())
                ->default(EventStatus::Draft);
            $table->timestampsTz();
            $table->unique('slug', 'idx_events_slug');
            $table->index('starts_at', 'idx_events_window');
            $table->index(['event_type', 'starts_at'], 'idx_events_type_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
