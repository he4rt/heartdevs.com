<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;

function usernameUniqueIndexMigration(): object
{
    return require base_path('app-modules/identity/database/migrations/2026_09_14_200835_make_users_username_unique_index_partial.php');
}

test('o rollback trunca o username antes de anexar o sufixo de duplicata', function (): void {
    $baseUsername = str_repeat('a', 255);

    $deleted = User::factory()->create(['username' => $baseUsername, 'deleted_at' => now()]);
    $active = User::factory()->create(['username' => $baseUsername]);

    usernameUniqueIndexMigration()->down();

    $renamed = DB::table('users')->where('id', $deleted->getKey())->value('username');

    expect(mb_strlen($renamed))->toBeLessThanOrEqual(255)
        ->and($renamed)->toEndWith('_dup_'.$deleted->getKey())
        ->and(DB::table('users')->where('id', $active->getKey())->value('username'))->toBe($baseUsername);

    usernameUniqueIndexMigration()->up();
});
