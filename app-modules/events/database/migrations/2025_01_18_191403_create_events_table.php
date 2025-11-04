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
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->nullOnDelete();
            $table->smallInteger('event_type'); // Meetup or Workshop
            $table->boolean('active');
            $table->string('slug');
            $table->string('title');
            $table->text('description');
            $table->timestamp('event_at'); // Date
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('location');
            $table->integer('max_attendees');
            $table->integer('attendees_count')->default(0);
            $table->integer('waitlist_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //        Schema::dropIfExists('events');
    }
};
