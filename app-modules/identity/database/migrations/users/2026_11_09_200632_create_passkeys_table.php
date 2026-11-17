<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The table laravel/passkeys expects, with a UUID user key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', static function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->index()->constrained('identity_users')->cascadeOnDelete();
            $table->string('name');
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();
        });
    }
};
