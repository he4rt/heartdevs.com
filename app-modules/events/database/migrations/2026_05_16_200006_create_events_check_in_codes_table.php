<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_check_in_codes', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->date('event_date');
            $table->string('code', 16);
            $table->timestampTz('starts_at');
            $table->timestampTz('expires_at');
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestampsTz();

            $table->unique(['event_id', 'event_date', 'code'], 'idx_check_in_codes_unique');
            $table->index(['code', 'expires_at'], 'idx_check_in_codes_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_check_in_codes');
    }
};
