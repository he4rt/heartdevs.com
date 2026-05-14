<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events_talks', function (Blueprint $table): void {
            $table->dropForeignKey('events_talks_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::table('events_attendees', function (Blueprint $table): void {
            $table->dropForeignKey('events_attendees_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::table('events_sponsors', function (Blueprint $table): void {
            $table->dropForeignKey('events_sponsors_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::table('events_agenda', function (Blueprint $table): void {
            $table->dropForeignKey('events_agenda_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::dropIfExists('events');

        Schema::create('events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('event_type');
            $table->boolean('active')->default(true);
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->timestamp('event_at');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('location');
            $table->integer('max_attendees');
            $table->integer('attendees_count')->default(0);
            $table->integer('waitlist_count')->default(0);
            $table->timestamps();
        });

        Schema::table('events_talks', function (Blueprint $table): void {
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
        });

        Schema::table('events_attendees', function (Blueprint $table): void {
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
        });

        Schema::table('events_sponsors', function (Blueprint $table): void {
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
        });

        Schema::table('events_agenda', function (Blueprint $table): void {
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events_talks', function (Blueprint $table): void {
            $table->dropForeignKey('events_talks_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::table('events_attendees', function (Blueprint $table): void {
            $table->dropForeignKey('events_attendees_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::table('events_sponsors', function (Blueprint $table): void {
            $table->dropForeignKey('events_sponsors_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::table('events_agenda', function (Blueprint $table): void {
            $table->dropForeignKey('events_agenda_event_id_foreign');
            $table->dropColumn('event_id');
        });

        Schema::dropIfExists('events');

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('event_type');
            $table->boolean('active');
            $table->string('slug');
            $table->string('title');
            $table->text('description');
            $table->timestamp('event_at');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('location');
            $table->integer('max_attendees');
            $table->integer('attendees_count')->default(0);
            $table->integer('waitlist_count')->default(0);
            $table->timestamps();
        });

        Schema::table('events_talks', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
        });

        Schema::table('events_attendees', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
        });

        Schema::table('events_sponsors', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
        });

        Schema::table('events_agenda', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
        });
    }
};
