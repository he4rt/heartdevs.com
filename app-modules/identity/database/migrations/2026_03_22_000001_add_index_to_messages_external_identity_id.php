<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', static function (Blueprint $table): void {
            $table->index('external_identity_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', static function (Blueprint $table): void {
            $table->dropIndex(['external_identity_id']);
        });
    }
};
