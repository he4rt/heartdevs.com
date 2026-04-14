<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordProfileAction;
use He4rt\IntegrationDiscord\ETL\DTOs\ConnectedAccountDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;

function discordProfile(array $overrides = []): array
{
    $default = [
        'user' => [
            'id' => '49615312957476864',
            'username' => '_tats',
            'global_name' => 'Madruguinha',
            'avatar' => '7638814994b449231c5ff17dd84500bd',
            'avatar_decoration_data' => null,
            'collectibles' => null,
            'discriminator' => '0',
            'display_name_styles' => null,
            'public_flags' => 0,
            'primary_guild' => null,
            'clan' => null,
            'flags' => 0,
            'banner' => null,
            'banner_color' => null,
            'accent_color' => null,
            'bio' => '',
        ],
        'connected_accounts' => [
            ['type' => 'twitch', 'id' => '81085454', 'name' => 'fewerygor', 'verified' => true],
        ],
        'premium_type' => 0,
        'premium_since' => null,
        'premium_guild_since' => null,
        'profile_themes_experiment_bucket' => 4,
        'user_profile' => [
            'bio' => '',
            'accent_color' => null,
            'pronouns' => '',
            'profile_effect' => null,
            'collectibles' => [],
        ],
        'badges' => [
            ['id' => 'legacy_username', 'description' => 'Originally known as Tats#0927', 'icon' => '6de6d34650760ba5551a79732e98ed60'],
        ],
        'guild_badges' => [],
        'widgets' => [],
        'guild_member' => [
            'avatar' => null,
            'banner' => null,
            'communication_disabled_until' => null,
            'flags' => 10,
            'joined_at' => '2024-02-29T02:34:52.077000+00:00',
            'nick' => null,
            'pending' => false,
            'premium_since' => null,
            'roles' => [],
            'unusual_dm_activity_until' => null,
            'collectibles' => null,
            'display_name_styles' => null,
            'bio' => '',
            'mute' => false,
            'deaf' => false,
        ],
        'guild_member_profile' => [
            'guild_id' => '452926217558163456',
            'pronouns' => '',
            'profile_effect' => null,
            'collectibles' => [],
        ],
        'legacy_username' => 'Tats#0927',
    ];

    if (array_key_exists('connected_accounts', $overrides) && $overrides['connected_accounts'] === []) {
        $default['connected_accounts'] = [];
        unset($overrides['connected_accounts']);
    }

    return array_replace_recursive($default, $overrides);
}

// DTO Tests

test('it creates DTO from discord dump format', function (): void {
    $profile = discordProfile();

    $dto = DiscordProfileDTO::fromDump($profile);

    expect($dto->discordId)->toBe('49615312957476864')
        ->and($dto->username)->toBe('_tats')
        ->and($dto->name)->toBe('Madruguinha')
        ->and($dto->joinedAt)->toBe('2024-02-29T02:34:52.077000+00:00');
});

test('it falls back to username when global_name is null', function (): void {
    $profile = discordProfile(['user' => ['global_name' => null]]);

    $dto = DiscordProfileDTO::fromDump($profile);

    expect($dto->name)->toBe('_tats');
});

test('it preserves entire dump in metadata', function (): void {
    $profile = discordProfile();

    $dto = DiscordProfileDTO::fromDump($profile);

    expect($dto->metadata)->toBe($profile)
        ->and($dto->metadata)->toHaveKeys([
            'user',
            'connected_accounts',
            'premium_type',
            'premium_since',
            'premium_guild_since',
            'profile_themes_experiment_bucket',
            'user_profile',
            'badges',
            'guild_badges',
            'widgets',
            'guild_member',
            'guild_member_profile',
            'legacy_username',
        ]);
});

// Action Tests

test('it creates user and external identity from discord profile', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $profile = discordProfile();
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $identity = $action->handle($dto, $tenant->getKey());

    expect($identity)->toBeInstanceOf(ExternalIdentity::class);

    $this->assertDatabaseHas('users', [
        'username' => '_tats',
        'name' => 'Madruguinha',
    ]);

    $this->assertDatabaseHas('external_identities', [
        'provider' => 'discord',
        'external_account_id' => '49615312957476864',
        'tenant_id' => $tenant->getKey(),
    ]);
});

test('it upserts external identity when already exists', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $action = resolve(ImportDiscordProfileAction::class);

    $profileOld = discordProfile(['user_profile' => ['bio' => 'old bio'], 'connected_accounts' => []]);
    $action->handle(DiscordProfileDTO::fromDump($profileOld), $tenant->getKey());

    $profileNew = discordProfile(['user_profile' => ['bio' => 'new bio'], 'connected_accounts' => []]);
    $identity = $action->handle(DiscordProfileDTO::fromDump($profileNew), $tenant->getKey());

    expect(ExternalIdentity::query()->count())->toBe(1)
        ->and(User::query()->where('username', '_tats')->count())->toBe(1)
        ->and($identity->metadata['user_profile']['bio'])->toBe('new bio');
});

test('it attaches user to tenant', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $dto = DiscordProfileDTO::fromDump(discordProfile());

    $action = resolve(ImportDiscordProfileAction::class);
    $identity = $action->handle($dto, $tenant->getKey());

    $this->assertDatabaseHas('tenant_users', [
        'user_id' => $identity->model_id,
        'tenant_id' => $tenant->getKey(),
    ]);
});

test('it stores complete discord metadata in external identity', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $profile = discordProfile();
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $identity = $action->handle($dto, $tenant->getKey());

    $identity->refresh();

    $metadata = $identity->metadata;

    expect($metadata)->toHaveKeys([
        'user',
        'connected_accounts',
        'badges',
        'guild_member',
        'user_profile',
        'premium_type',
        'legacy_username',
        'guild_member_profile',
    ])
        ->and($metadata['connected_accounts'])->toHaveCount(1)
        ->and($metadata['connected_accounts'][0]['type'])->toBe('twitch')
        ->and($metadata['badges'][0]['id'])->toBe('legacy_username')
        ->and($metadata['legacy_username'])->toBe('Tats#0927');
});

test('it sets connected_at to null when guild_member joined_at is absent', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $profile = discordProfile(['guild_member' => ['joined_at' => null]]);
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $identity = $action->handle($dto, $tenant->getKey());

    expect($identity->connected_at)->toBeNull();
});

test('it creates external identities for each connected account', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $profile = discordProfile([
        'connected_accounts' => [
            ['type' => 'twitch', 'id' => '81085454', 'name' => 'fewerygor', 'verified' => true],
            ['type' => 'github', 'id' => '123456', 'name' => 'he4rtdevs', 'verified' => true],
        ],
    ]);
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $action->handle($dto, $tenant->getKey());

    expect(ExternalIdentity::query()->count())->toBe(3)
        ->and(ExternalIdentity::query()->where('provider', 'discord')->exists())->toBeTrue()
        ->and(ExternalIdentity::query()->where('provider', 'twitch')->exists())->toBeTrue()
        ->and(ExternalIdentity::query()->where('provider', 'github')->exists())->toBeTrue();

    $user = User::query()->where('username', '_tats')->first();
    expect(ExternalIdentity::query()->where('model_id', $user->id)->count())->toBe(3);
});

test('it stores connected account metadata in external identity', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $profile = discordProfile([
        'connected_accounts' => [
            [
                'type' => 'steam',
                'id' => '123456789',
                'name' => 'he4rt_player',
                'verified' => true,
                'metadata' => ['game_count' => 42],
            ],
        ],
    ]);
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $action->handle($dto, $tenant->getKey());

    $steamIdentity = ExternalIdentity::query()->where('provider', 'steam')->first();

    expect($steamIdentity->metadata)->toHaveKeys(['type', 'id', 'name', 'verified', 'metadata'])
        ->and($steamIdentity->metadata['type'])->toBe('steam')
        ->and($steamIdentity->metadata['id'])->toBe('123456789')
        ->and($steamIdentity->metadata['name'])->toBe('he4rt_player')
        ->and($steamIdentity->metadata['metadata']['game_count'])->toBe(42);
});

test('it handles profile with no connected accounts', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $profile = discordProfile(['connected_accounts' => []]);
    $dto = DiscordProfileDTO::fromDump($profile);

    $action = resolve(ImportDiscordProfileAction::class);
    $action->handle($dto, $tenant->getKey());

    expect(ExternalIdentity::query()->count())->toBe(1)
        ->and(ExternalIdentity::query()->where('provider', 'discord')->exists())->toBeTrue();
});

test('it upserts connected account external identities', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $action = resolve(ImportDiscordProfileAction::class);

    $profileOld = discordProfile([
        'connected_accounts' => [
            ['type' => 'spotify', 'id' => 'user123', 'name' => 'old_name', 'verified' => false],
        ],
    ]);
    $action->handle(DiscordProfileDTO::fromDump($profileOld), $tenant->getKey());

    expect(ExternalIdentity::query()->count())->toBe(2)
        ->and(ExternalIdentity::query()->where('provider', 'spotify')->first()->metadata['name'])->toBe('old_name');

    $profileNew = discordProfile([
        'connected_accounts' => [
            ['type' => 'spotify', 'id' => 'user123', 'name' => 'new_name', 'verified' => true],
        ],
    ]);
    $action->handle(DiscordProfileDTO::fromDump($profileNew), $tenant->getKey());

    expect(ExternalIdentity::query()->count())->toBe(2)
        ->and(ExternalIdentity::query()->where('provider', 'spotify')->count())->toBe(1)
        ->and(ExternalIdentity::query()->where('provider', 'spotify')->first()->metadata['name'])->toBe('new_name');
});

test('connected account dto creates from dump format', function (): void {
    $account = [
        'type' => 'github',
        'id' => '123456',
        'name' => 'he4rtdevs',
        'verified' => true,
        'metadata' => ['extra' => 'data'],
    ];

    $dto = ConnectedAccountDTO::fromDump($account);

    expect($dto->provider)->toBe(IdentityProvider::GitHub)
        ->and($dto->externalAccountId)->toBe('123456')
        ->and($dto->name)->toBe('he4rtdevs')
        ->and($dto->verified)->toBeTrue()
        ->and($dto->metadata)->toBe($account);
});
