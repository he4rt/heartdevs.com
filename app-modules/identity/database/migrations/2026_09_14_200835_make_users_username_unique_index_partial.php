<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Uma conta soft-deletada não pode travar o `username` pra sempre — sem
     * isso, `MergeAccountsAction` (e qualquer novo cadastro) esbarra em
     * "duplicate key" ao tentar reaproveitar o username de um usuário
     * apenas soft-deletado.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_username_unique');
        DB::statement('CREATE UNIQUE INDEX users_username_unique ON users (username) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX users_username_unique');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_username_unique UNIQUE (username)');
    }
};
