<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_contributions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('repo');
            $table->string('actor_login');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('type');
            $table->string('external_ref');
            $table->string('target_ref')->nullable();
            $table->timestamp('occurred_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['repo', 'type', 'external_ref'], 'uniq_github_contributions_ref');
            $table->index(['repo', 'occurred_at'], 'idx_github_contributions_repo_time');
            $table->index('actor_id', 'idx_github_contributions_actor');
            $table->index(['type', 'occurred_at'], 'idx_github_contributions_type_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_contributions');
    }
};
