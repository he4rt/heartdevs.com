<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events_check_in_codes', static function (Blueprint $table): void {
            $table->timestampTz('revoked_at')->nullable()->after('uses_count');
        });
    }

    public function down(): void
    {
        Schema::table('events_check_in_codes', static function (Blueprint $table): void {
            $table->dropColumn('revoked_at');
        });
    }
};
