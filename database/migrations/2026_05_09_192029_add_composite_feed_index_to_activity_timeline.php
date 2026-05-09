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
            $table->index(
                ['tenant_id', 'parent_id', 'is_ignored', 'created_at'],
                'activity_timeline_feed_composite_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->dropIndex('activity_timeline_feed_composite_index');
        });
    }
};
