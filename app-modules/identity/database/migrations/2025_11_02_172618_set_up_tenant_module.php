<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $tables = [
            'providers' => ['tenant_id', 'model_type', 'model_id'],
            'messages' => [],
            'characters' => [],
            'voice_messages' => [],
            'feedbacks' => [],
            'feedback_reviews' => [],
            'meetings' => [],
            'badges' => [],
            'characters_badges' => [],
            'seasons_rankings' => [],
            'seasons' => [],
        ];

        // Guarded so `migrate:fresh` stays green after multi-tenancy removal:
        // if the tenants table is gone, or a target table is missing / already
        // carries the column, there is nothing to set up.
        if (!Schema::hasTable('tenants')) {
            return;
        }

        foreach ($tables as $table => $indexableColumns) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, static function (Blueprint $table) use ($indexableColumns): void {
                $table->foreignUuid('tenant_id')
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();

                if ($indexableColumns !== []) {
                    $table->index($indexableColumns);
                }
            });
        }
    }

    public function down(): void {}
};
