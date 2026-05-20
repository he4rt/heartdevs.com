<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE voice_messages ALTER COLUMN occurred_at TYPE timestamptz USING occurred_at AT TIME ZONE 'UTC'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE voice_messages ALTER COLUMN occurred_at TYPE timestamp(0) WITHOUT TIME ZONE');
    }
};
