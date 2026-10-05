<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streamers', function (Blueprint $table): void {
            $table->timestampTz('chat_cleared_at')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('streamers', function (Blueprint $table): void {
            $table->dropColumn('chat_cleared_at');
        });
    }
};
