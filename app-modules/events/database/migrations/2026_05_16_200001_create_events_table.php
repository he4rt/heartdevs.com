<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('slug', 120);
            $table->string('title', 200);
            $table->longText('description')->nullable();
            $table->string('event_type', 20);
            $table->string('location')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('active')->default(false);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'slug'], 'idx_events_tenant_slug');
            $table->index(['tenant_id', 'starts_at'], 'idx_events_tenant_window');
            $table->index(['event_type', 'starts_at'], 'idx_events_type_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
