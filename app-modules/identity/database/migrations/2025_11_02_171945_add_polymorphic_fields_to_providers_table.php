<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table): void {
            $table->string('model_type')->after('id')
                ->default(User::class)
                ->nullable();

            $table->renameColumn('user_id', 'model_id');

            $table->index(['model_type', 'model_id']);
        });
    }
};
