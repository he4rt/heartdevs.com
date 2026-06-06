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
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->string('repo');
            $table->string('actor_login');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('type');
            $table->string('external_ref');
            $table->string('target_ref')->nullable();
            $table->timestamp('occurred_at');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            // Isolamento por tenant: a mesma contribuição de um repo compartilhado
            // é gravada uma vez por comunidade que acompanha o repo.
            $table->unique(['tenant_id', 'repo', 'type', 'external_ref'], 'uniq_github_contributions_ref');
            $table->index(['tenant_id', 'occurred_at'], 'idx_github_contributions_tenant_time');
            $table->index('actor_id', 'idx_github_contributions_actor');
            $table->index(['tenant_id', 'type', 'occurred_at'], 'idx_github_contributions_type_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_contributions');
    }
};
