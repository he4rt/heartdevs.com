<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE activity_post_entries ALTER COLUMN id DROP DEFAULT');
        DB::statement('ALTER TABLE activity_post_entries ALTER COLUMN id TYPE uuid USING gen_random_uuid()');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE activity_post_entries ALTER COLUMN id TYPE bigint USING NULL');
    }
};
