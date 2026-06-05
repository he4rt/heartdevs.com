<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_repositories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name')->unique();   // owner/repo
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_backfilled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
