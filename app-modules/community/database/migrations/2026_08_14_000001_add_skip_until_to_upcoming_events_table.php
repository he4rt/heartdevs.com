<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upcoming_events', static function (Blueprint $table): void {
            $table->timestampTz('skip_until')->nullable()->after('skip_next_occurrence');
        });
    }

    public function down(): void
    {
        Schema::table('upcoming_events', static function (Blueprint $table): void {
            $table->dropColumn('skip_until');
        });
    }
};
