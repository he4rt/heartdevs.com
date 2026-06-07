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
        if (!Schema::hasTable('seasons')) {
            Schema::create('seasons', function (Blueprint $table): void {
                $table->uuid('id');
                $table->string('name');
                $table->text('description');
                $table->timestampTz('started_at')->nullable();
                $table->timestampTz('ended_at')->nullable();
                $table->integer('messages_count')->default(0);
                $table->integer('participants_count')->default(0);
                $table->integer('meeting_count')->default(0);
                $table->integer('badges_count')->default(0);
                $table->timestampsTz();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
