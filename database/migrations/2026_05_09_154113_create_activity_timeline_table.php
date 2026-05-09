<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
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
        Schema::create('activity_timeline', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignIdFor(User::class, 'user_id');
            $table->morphs('postable');
            $table->foreignUuid('root_id')->nullable();
            $table->foreignUuid('parent_id')->nullable();
            $table->boolean('is_reported')->default(false);
            $table->boolean('is_ignored')->default(false);
            $table->boolean('pinned')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();

            $table->unique(['id'], 'activity_timeline_pkey');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_timeline');
    }
};
