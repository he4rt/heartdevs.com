<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "CREATE UNIQUE INDEX squad_members_squad_id_captain_unique ON squad_members (squad_id) WHERE role = 'captain'"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS squad_members_squad_id_captain_unique');
    }
};
