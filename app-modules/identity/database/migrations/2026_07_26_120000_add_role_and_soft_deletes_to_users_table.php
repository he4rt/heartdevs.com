<?php

declare(strict_types=1);

use He4rt\Identity\User\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('role')->default(Role::Member->value)->after('is_donator');
            $table->softDeletesTz();
        });

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_username_unique');

        // Scoped to active rows only: soft-deleted users must not block a
        // username from being reused (e.g. account merges reassign it).
        DB::statement('CREATE UNIQUE INDEX users_username_unique ON users (username) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_username_unique');
        DB::statement('DROP INDEX IF EXISTS users_username_unique');

        $this->deduplicateUsernames();

        Schema::table('users', static function (Blueprint $table): void {
            $table->unique('username');
            $table->dropColumn(['role', 'deleted_at']);
        });
    }

    /**
     * up() only enforced uniqueness among active rows, so a soft-deleted row
     * may share a username with an active row (or another trashed row).
     * Rename those duplicates before restoring the global unique constraint,
     * keeping the active row (or the oldest one, if all are trashed)
     * untouched. Candidates are checked against every username already in
     * the table (not just the other duplicates), incrementing a counter
     * until a free one is found, so this can't collide with an unrelated
     * pre-existing username. The suffix is intentionally neutral (not
     * "_deleted_"): the renamed row isn't necessarily trashed.
     */
    private function deduplicateUsernames(): void
    {
        /** @var array<string, true> $takenUsernames */
        $takenUsernames = DB::table('users')->pluck('username')
            ->mapWithKeys(static fn (string $username): array => [$username => true])
            ->all();

        $duplicateLosers = DB::select(<<<'SQL'
            SELECT id, username
            FROM (
                SELECT id, username, row_number() OVER (
                    PARTITION BY username
                    ORDER BY deleted_at IS NULL DESC, created_at ASC
                ) AS row_number
                FROM users
            ) ranked
            WHERE row_number > 1
        SQL);

        foreach ($duplicateLosers as $row) {
            /** @var array{id: string, username: string} $row */
            $row = (array) $row;
            $id = $row['id'];
            $base = $row['username'].'_dup_'.mb_substr($id, 0, 8);
            $candidate = $base;
            $attempt = 1;

            while (isset($takenUsernames[$candidate])) {
                $candidate = $base.'_'.$attempt;
                $attempt++;
            }

            $takenUsernames[$candidate] = true;

            DB::table('users')->where('id', $id)->update(['username' => $candidate]);
        }
    }
};
