<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_repositories', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name');   // owner/repo
            $table->boolean('enabled')->default(value: true);
            $table->timestampTz('last_backfilled_at')->nullable();
            $table->timestampsTz();

            // A allowlist é global: cada repo público é acompanhado uma única vez.
            $table->unique('full_name', 'uniq_github_repositories_repo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
