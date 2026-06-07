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
        if (!Schema::hasTable('provider_tokens')) {
            Schema::create('provider_tokens', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('provider_id')->constrained('providers')->cascadeOnDelete();
                $table->string('access_token');
                $table->string('refresh_token');
                $table->integer('expires_in')->nullable();
                $table->timestampsTz();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_tokens');
    }
};
