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
            $table->foreignUuid('tenant_id')->constrained('tenants');
            $table->string('full_name');   // owner/repo
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_backfilled_at')->nullable();
            $table->timestamps();

            // A allowlist é por tenant: cada comunidade mantém a sua, e o mesmo
            // repo público pode ser acompanhado por mais de uma comunidade.
            $table->unique(['tenant_id', 'full_name'], 'uniq_github_repositories_tenant_repo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
