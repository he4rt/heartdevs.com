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
            $table->string('host_name', 255)->nullable()->after('external_url');
            $table->string('host_role', 255)->nullable()->after('host_name');
        });
    }

    public function down(): void
    {
        Schema::table('upcoming_events', static function (Blueprint $table): void {
            $table->dropColumn(['host_name', 'host_role']);
        });
    }
};
