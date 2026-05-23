<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events_check_ins', function (Blueprint $table): void {
            $table->timestampTz('checked_in_at')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('events_check_ins', function (Blueprint $table): void {
            $table->dropColumn('checked_in_at');
        });
    }
};
