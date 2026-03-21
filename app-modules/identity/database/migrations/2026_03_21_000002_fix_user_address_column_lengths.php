<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_address', function (Blueprint $table): void {
            $table->string('country')->nullable()->change();
            $table->string('state')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_address', function (Blueprint $table): void {
            $table->string('country', 4)->nullable()->change();
            $table->string('state', 4)->nullable()->change();
        });
    }
};
