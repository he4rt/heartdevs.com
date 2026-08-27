<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_identities', static function (Blueprint $table): void {
            $table->string('email')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('external_identities', static function (Blueprint $table): void {
            $table->dropColumn('email');
        });
    }
};
