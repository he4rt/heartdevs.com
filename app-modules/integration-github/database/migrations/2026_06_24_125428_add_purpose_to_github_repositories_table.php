<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('github_repositories', static function (Blueprint $table): void {
            $table->string('purpose')->nullable();
        });

        DB::table('github_repositories')->update(['purpose' => 'contributions']);
    }
};
