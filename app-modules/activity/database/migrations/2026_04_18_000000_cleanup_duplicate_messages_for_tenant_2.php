<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            DELETE FROM messages
            WHERE tenant_id = 2
              AND id IN (
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (
                        PARTITION BY tenant_id, provider_message_id
                        ORDER BY created_at ASC, id ASC
                    ) AS rn
                    FROM messages
                    WHERE tenant_id = 2
                      AND provider_message_id IS NOT NULL
                ) ranked
                WHERE rn > 1
            )
        ');
    }

    public function down(): void {}
};
