<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->foreignIdFor(Tenant::class, 'tenant_id')->after('user_id');
            $table->dropColumn('is_reported');

            $table->index(['tenant_id', 'created_at'], 'activity_timeline_tenant_feed_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_timeline', function (Blueprint $table): void {
            $table->dropIndex('activity_timeline_tenant_feed_index');
            $table->dropColumn('tenant_id');
            $table->boolean('is_reported')->default(false);
        });
    }
};
