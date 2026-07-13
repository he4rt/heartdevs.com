<?php

declare(strict_types=1);

use He4rt\IntegrationGithub\Enums\ContributionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_contributions', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('repo');
            $table->string('actor_login');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('type')->comment(ContributionType::stringifyCases());
            $table->string('external_ref');
            $table->string('target_ref')->nullable();
            $table->timestampTz('occurred_at');
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            // Cada contribuição de um repo é gravada uma única vez.
            $table->unique(['repo', 'type', 'external_ref'], 'uniq_github_contributions_ref');
            $table->index('occurred_at', 'idx_github_contributions_time');
            $table->index('actor_id', 'idx_github_contributions_actor');
            $table->index(['type', 'occurred_at'], 'idx_github_contributions_type_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_contributions');
    }
};
