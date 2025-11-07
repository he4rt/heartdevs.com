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
        if (! Schema::hasTable('meeting_types')) {
            Schema::create('meeting_types', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->integer('week_day')->comment('Week day of event');
                $table->integer('start_at')->comment('Number of minutes past midnight');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_types');
    }
};
