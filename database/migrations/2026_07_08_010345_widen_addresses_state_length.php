<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', static function (Blueprint $table): void {
            $table->string('state', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('addresses', static function (Blueprint $table): void {
            $table->string('state', 4)->nullable()->change();
        });
    }
};
