<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('twitch_subscriptions', function (Blueprint $table): void {
            $table->foreignUuid('streamer_source_id')->nullable()->constrained('streamer_sources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('twitch_subscriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('streamer_source_id');
        });
    }
};
