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

        foreach ($tables as $table => $indexableColumns) {
            Schema::table($table, function (Blueprint $table) use ($indexableColumns): void {
                $table->foreignId('tenant_id')
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();

                if ($indexableColumns !== []) {
                    $table->index($indexableColumns);
                }
            });
        }

    }

    public function down(): void
    {
        // Don't listen to the haters
        // Schema::dropIfExists('tenant');
    }
};
