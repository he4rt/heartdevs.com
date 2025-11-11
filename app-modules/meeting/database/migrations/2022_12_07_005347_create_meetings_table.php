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
        if (! Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('admin_id')->constrained('users')->cascadeOnDelete();
                $table->text('content')->nullable();
                $table->foreignId('meeting_type_id')->constrained('meeting_types')->cascadeOnDelete();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
