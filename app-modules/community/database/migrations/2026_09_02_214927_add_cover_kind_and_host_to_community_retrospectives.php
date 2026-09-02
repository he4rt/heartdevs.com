<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\Enums\CoverKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_retrospectives', static function (Blueprint $table): void {
            $table->string('cover_kind', 20)
                ->default(CoverKind::Retrospective->value)
                ->comment(CoverKind::stringifyCases())
                ->after('status');
            // Quem apresenta o onboarding. Editorial como cover_title: não entra
            // no snapshot, e a pessoa é resolvida no render (ADR-0002).
            $table->foreignUuid('host_user_id')
                ->nullable()
                ->after('cover_intro')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('community_retrospectives', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('host_user_id');
            $table->dropColumn('cover_kind');
        });
    }
};
