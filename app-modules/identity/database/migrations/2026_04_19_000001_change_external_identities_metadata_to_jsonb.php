<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE external_identities ALTER COLUMN metadata TYPE jsonb USING metadata::jsonb');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE external_identities ALTER COLUMN metadata TYPE json USING metadata::json');
    }
};
