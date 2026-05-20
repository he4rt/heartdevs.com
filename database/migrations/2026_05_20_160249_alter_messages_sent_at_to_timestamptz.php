<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE messages ALTER COLUMN sent_at TYPE timestamptz USING sent_at AT TIME ZONE 'UTC'");
    }
};
