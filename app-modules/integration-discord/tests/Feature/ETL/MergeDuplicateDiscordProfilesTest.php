<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * @return array<int, User|Collection<int, User>|ExternalIdentity|Collection<int, ExternalIdentity>>
 */
function makeImportedDup(Tenant $tenant, string $discordId, string $username, array $metadataExtras = []): array
{
    $newUser = User::factory()->create([
        'username' => $username,
        'name' => $username,
        'created_at' => '2026-05-01 21:30:00',
    ]);

    $identity = ExternalIdentity::factory()
        ->morphFor()
        ->create([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $discordId,
            'tenant_id' => $tenant->getKey(),
            'model_id' => $newUser->id,
            'metadata' => $metadataExtras,
        ]);

    return [$newUser, $identity];
}

function makeOrphan(string $username, ?string $createdAt = '2025-08-10 00:00:00'): User
{
    return User::factory()->create([
        'username' => $username,
        'name' => $username,
        'created_at' => $createdAt,
    ]);
}

test('dry-run does not modify the database', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('Tats#0927');
    [$newUser] = makeImportedDup($tenant, '49615312957476864', '_tats', ['legacy_username' => 'Tats#0927']);

    Artisan::call('discord:merge-duplicate-profiles', ['--dry-run' => true, '--from-date' => '2026-05-01']);

    expect(User::query()->find($newUser->id))->not->toBeNull()
        ->and(User::query()->find($orphan->id))->not->toBeNull()
        ->and(User::query()->find($orphan->id)->username)->toBe('Tats#0927');
});

test('merges via metadata.legacy_username (preferred match)', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('Tats#0927');
    [$newUser, $identity] = makeImportedDup($tenant, '49615312957476864', '_tats', ['legacy_username' => 'Tats#0927']);

    Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01']);

    $orphan->refresh();
    $identity->refresh();

    expect(User::query()->find($newUser->id))->toBeNull()
        ->and($orphan->username)->toBe('_tats')
        ->and((string) $identity->model_id)->toBe((string) $orphan->id);
});

test('merges via badge legacy_username description fallback', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('OldName#1234');
    [$newUser, $identity] = makeImportedDup($tenant, '111', 'newname', [
        'badges' => [
            ['id' => 'legacy_username', 'description' => 'Originally known as OldName#1234'],
        ],
    ]);

    Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01']);

    $orphan->refresh();
    $identity->refresh();

    expect(User::query()->find($newUser->id))->toBeNull()
        ->and($orphan->username)->toBe('newname')
        ->and((string) $identity->model_id)->toBe((string) $orphan->id);
});

test('merges via #0 heuristic when metadata has no legacy_username', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('xpto#0');
    [$newUser, $identity] = makeImportedDup($tenant, '222', 'xpto');

    Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01']);

    $orphan->refresh();
    $identity->refresh();

    expect(User::query()->find($newUser->id))->toBeNull()
        ->and($orphan->username)->toBe('xpto')
        ->and((string) $identity->model_id)->toBe((string) $orphan->id);
});

test('skips when no orphan candidate exists (genuinely new user)', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    [$newUser] = makeImportedDup($tenant, '333', 'brand_new');

    $exitCode = Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01']);

    expect($exitCode)->toBe(0)
        ->and(User::query()->find($newUser->id))->not->toBeNull();
});

test('skips when multiple orphan candidates match (conflict)', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphanA = makeOrphan('ambig#0');
    $orphanB = makeOrphan('ambig#9999');
    [$newUser] = makeImportedDup($tenant, '444', 'ambig', [
        'badges' => [
            ['id' => 'legacy_username', 'description' => 'Originally known as ambig#9999'],
        ],
    ]);

    Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01']);

    expect(User::query()->find($newUser->id))->not->toBeNull()
        ->and(User::query()->find($orphanA->id))->not->toBeNull()
        ->and(User::query()->find($orphanB->id))->not->toBeNull();
});

test('reassigns user_id on tables with simple FK', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('xpto#0');
    [$newUser] = makeImportedDup($tenant, '555', 'xpto');

    $charId = (string) Uuid::uuid4();
    DB::table('characters')->insert([
        'id' => $charId,
        'user_id' => $newUser->id,
        'tenant_id' => $tenant->getKey(),
        'experience' => 100,
        'reputation' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01']);

    $row = DB::table('characters')->where('id', $charId)->first();
    expect($row)->not->toBeNull()
        ->and((string) $row->user_id)->toBe((string) $orphan->id);
});

test('dedupes pivot rows when both users have same key', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('xpto#0');
    [$newUser] = makeImportedDup($tenant, '666', 'xpto');

    DB::table('tenant_users')->insert([
        ['tenant_id' => $tenant->getKey(), 'user_id' => $orphan->id, 'created_at' => now(), 'updated_at' => now()],
        ['tenant_id' => $tenant->getKey(), 'user_id' => $newUser->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01']);

    $count = DB::table('tenant_users')
        ->where('tenant_id', $tenant->getKey())
        ->where('user_id', $orphan->id)
        ->count();
    expect($count)->toBe(1);
});

test('merges via --pairs-file JSONL when provided (file-based strategy)', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('renamed-once');
    [$newUser, $identity] = makeImportedDup($tenant, '7777', 'renamed-twice');

    $jsonl = json_encode([
        'tenant_id' => $tenant->getKey(),
        'external_account_id' => '7777',
        'old_user_id' => $orphan->id,
        'old_username' => $orphan->username,
        'new_user_id' => $newUser->id,
        'new_username' => $newUser->username,
    ]);

    $path = tempnam(sys_get_temp_dir(), 'pairs-').'.jsonl';
    file_put_contents($path, $jsonl."\n");

    Artisan::call('discord:merge-duplicate-profiles', ['--pairs-file' => $path]);

    @unlink($path);

    $orphan->refresh();
    $identity->refresh();

    expect(User::query()->find($newUser->id))->toBeNull()
        ->and($orphan->username)->toBe('renamed-twice')
        ->and((string) $identity->model_id)->toBe((string) $orphan->id);
});

test('--pairs-file skips lines whose users no longer exist', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $orphan = makeOrphan('valid-orphan');
    [$newUser] = makeImportedDup($tenant, '8888', 'valid-dup');

    $lines = [
        json_encode([
            'tenant_id' => $tenant->getKey(),
            'external_account_id' => '8888',
            'old_user_id' => $orphan->id,
            'new_user_id' => $newUser->id,
            'new_username' => 'valid-dup',
        ]),
        json_encode([
            'tenant_id' => $tenant->getKey(),
            'external_account_id' => '0000',
            'old_user_id' => '00000000-0000-0000-0000-000000000000',
            'new_user_id' => '11111111-1111-1111-1111-111111111111',
            'new_username' => 'ghost',
        ]),
    ];

    $path = tempnam(sys_get_temp_dir(), 'pairs-').'.jsonl';
    file_put_contents($path, implode("\n", $lines)."\n");

    Artisan::call('discord:merge-duplicate-profiles', ['--pairs-file' => $path]);

    @unlink($path);

    expect(User::query()->find($newUser->id))->toBeNull()
        ->and(User::query()->find($orphan->id))->not->toBeNull();
});

test('respects --limit option', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    makeOrphan('a#0');
    makeOrphan('b#0');
    [$dup1] = makeImportedDup($tenant, '101', 'a');
    [$dup2] = makeImportedDup($tenant, '102', 'b');

    Artisan::call('discord:merge-duplicate-profiles', ['--from-date' => '2026-05-01', '--limit' => 1]);

    $remaining = User::query()
        ->whereIn('id', [$dup1->id, $dup2->id])
        ->count();

    expect($remaining)->toBe(1);
});
