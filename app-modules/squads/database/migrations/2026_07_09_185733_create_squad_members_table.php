<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squad_members', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('squad_id')->constrained('squads')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30);
            $table->timestampTz('joined_at');
            $table->timestampTz('left_at')->nullable();
            $table->timestampsTz();

            $table->unique(['squad_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_members');
    }
};
