<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'mails';

        Schema::table($table, static function (Blueprint $blueprint) use ($table): void {
            if (!Schema::hasColumn($table, 'scheduled_at')) {
                $blueprint->timestampTz('scheduled_at')->nullable()->after('unsubscribed_at');
            }

            if (!Schema::hasColumn($table, 'suppressed_at')) {
                $blueprint->timestampTz('suppressed_at')->nullable()->after('scheduled_at');
            }
        });
    }
};
