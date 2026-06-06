<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_messages', function (Blueprint $table): void {
            $table->string('channel_id')->nullable()->after('channel_name');
        });
    }
};
