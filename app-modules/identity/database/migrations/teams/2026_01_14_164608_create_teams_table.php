<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_teams', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('identity_users');
            $table->string('name');
            $table->string('description');
            $table->string('slug');
            $table->string('status');
            $table->string('contact_email');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }
};
