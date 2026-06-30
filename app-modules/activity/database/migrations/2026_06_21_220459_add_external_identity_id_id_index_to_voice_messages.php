<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // filter by external_identity_id, order by id desc, limit 1.
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS voice_messages_external_identity_id_id_index ON voice_messages (external_identity_id, id)');
    }
};
