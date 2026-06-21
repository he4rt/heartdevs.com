<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_messages', static function (Blueprint $table): void {
            // filter by external_identity_id, order by id desc, limit 1.
            $table->index(['external_identity_id', 'id'], 'voice_messages_external_identity_id_id_index');
        });
    }
};
