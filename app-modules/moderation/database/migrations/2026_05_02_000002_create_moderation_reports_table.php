<?php

declare(strict_types=1);

use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_reports', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('moderation_cases')->cascadeOnDelete();
            $table->foreignUuid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 30)->comment(ViolationType::stringifyCases());
            $table->text('details')->nullable();
            $table->string('platform', 20)->comment(Platform::stringifyCases());
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['case_id', 'reporter_id'], 'idx_reports_case_reporter');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_reports');
    }
};
