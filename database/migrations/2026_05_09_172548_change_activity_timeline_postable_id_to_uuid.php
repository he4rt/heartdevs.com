<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->dropIndex(['postable_type', 'postable_id']);
        });

        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->string('postable_id', 36)->change();
        });

        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->index(['postable_type', 'postable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->dropIndex(['postable_type', 'postable_id']);
        });

        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->unsignedBigInteger('postable_id')->change();
        });

        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->index(['postable_type', 'postable_id']);
        });
    }
};
