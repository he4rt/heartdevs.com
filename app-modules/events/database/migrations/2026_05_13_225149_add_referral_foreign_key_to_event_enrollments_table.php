<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_enrollments', function (Blueprint $table): void {
            $table->foreign('referral_id')
                ->references('id')->on('event_referrals')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_enrollments', function (Blueprint $table): void {
            $table->dropForeign(['referral_id']);
        });
    }
};
