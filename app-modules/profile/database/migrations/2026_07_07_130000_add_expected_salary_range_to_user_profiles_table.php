<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', static function (Blueprint $table): void {
            $table->decimal('expected_salary_min', 10, 2)->nullable();
            $table->decimal('expected_salary_max', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', static function (Blueprint $table): void {
            $table->dropColumn(['expected_salary_min', 'expected_salary_max']);
        });
    }
};
