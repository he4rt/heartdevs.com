<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_post_entries', function (Blueprint $table): void {
            $table->text('content')->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_post_entries', function (Blueprint $table): void {
            $table->string('content')->change();
        });
    }
};
