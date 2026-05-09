<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_timeline', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(Tenant::class, 'tenant_id');
            $table->uuidMorphs('postable');
            $table->foreignUuid('root_id')->nullable();
            $table->foreignUuid('parent_id')->nullable();
            $table->boolean('is_ignored')->default(false);
            $table->boolean('pinned')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'created_at'], 'activity_timeline_tenant_feed_index');
            $table->index(['tenant_id', 'parent_id', 'is_ignored', 'created_at'], 'activity_timeline_feed_composite_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_timeline');
    }
};
