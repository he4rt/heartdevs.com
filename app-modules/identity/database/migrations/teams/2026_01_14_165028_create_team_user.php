<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_team_user', static function (Blueprint $table): void {
            $table->foreignUuid('team_id')->constrained('identity_teams');
            $table->foreignUuid('user_id')->constrained('identity_users');

            $table->primary(['team_id', 'user_id']);
            $table->timestampsTz();
        });
    }
};
