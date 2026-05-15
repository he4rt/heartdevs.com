<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events_attendees', function (Blueprint $table): void {
            $table->string('state')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_method')->nullable();
            $table->integer('xp_awarded')->nullable();
            $table->float('streak_multiplier')->nullable();
        });
    }
};
