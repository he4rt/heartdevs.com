<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters_wallet', static function (Blueprint $table): void {
            $table->id();
            $table->integer('balance')->default(0);
            $table->foreignUuid('character_id')->constrained('characters')->cascadeOnDelete();
            $table->timestampsTz();
        });
    }
};
