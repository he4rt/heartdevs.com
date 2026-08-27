<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('alter table public.providers drop constraint if exists providers_user_id_foreign');
        }

        Schema::table('providers', static function (Blueprint $table): void {
            $table->string('user_id')->nullable()->change();
        });
    }
};
