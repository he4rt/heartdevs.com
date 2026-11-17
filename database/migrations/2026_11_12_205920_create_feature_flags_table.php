<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', static function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(new Expression('gen_random_uuid()'));
            $table->string('name');
            $table->string('scope');
            $table->text('value');
            $table->timestampsTz();

            $table->unique(['name', 'scope']);
        });
    }
};
