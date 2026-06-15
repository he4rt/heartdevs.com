<?php

declare(strict_types=1);

use He4rt\Activity\Voice\Actions\NewVoiceMessage;
use He4rt\Activity\Voice\DTOs\NewVoiceMessageDTO;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Gamification\Character\Enums\VoiceStatesEnum;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unmuted voice state awards multiplier times level', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 4_500]); // level 10

    ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456',
        ]);

    $dto = new NewVoiceMessageDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '123456',
        voiceState: VoiceStatesEnum::Unmuted,
        channelName: 'general-voice',
        channelId: '111222333',
        username: 'testuser',
    );

    resolve(NewVoiceMessage::class)->persist($dto);

    // unmuted multiplier (3) * level (10) = 30
    expect($character->fresh()->experience)->toBe(4_530);

    $voice = Voice::query()->latest()->first();
    expect($voice)->not->toBeNull()
        ->and($voice->obtained_experience)->toBe(30)
        ->and($voice->state)->toBe('unmuted')
        ->and($voice->channel_name)->toBe('general-voice')
        ->and($voice->channel_id)->toBe('111222333')
        ->and($voice->tenant_id)->toBe($tenant->id)
        ->and($voice->occurred_at)->not->toBeNull();
});

test('muted voice state awards reduced xp', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 4_500]); // level 10

    ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '789',
        ]);

    $dto = new NewVoiceMessageDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '789',
        voiceState: VoiceStatesEnum::Muted,
        channelName: 'general-voice',
    );

    resolve(NewVoiceMessage::class)->persist($dto);

    // muted multiplier (1) * level (10) = 10
    expect($character->fresh()->experience)->toBe(4_510);

    $voice = Voice::query()->latest()->first();
    expect($voice)->not->toBeNull()
        ->and($voice->obtained_experience)->toBe(10)
        ->and($voice->state)->toBe('muted');
});

test('disabled voice state awards zero xp', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 4_500]); // level 10

    ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '456',
        ]);

    $dto = new NewVoiceMessageDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '456',
        voiceState: VoiceStatesEnum::Disabled,
        channelName: 'general-voice',
    );

    resolve(NewVoiceMessage::class)->persist($dto);

    expect($character->fresh()->experience)->toBe(4_500);

    $voice = Voice::query()->latest()->first();
    expect($voice)->not->toBeNull()
        ->and($voice->obtained_experience)->toBe(0)
        ->and($voice->state)->toBe('disabled');
});

test('records channel name, channel id and occurred_at', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 0]);

    ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '321',
        ]);

    $dto = new NewVoiceMessageDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '321',
        voiceState: VoiceStatesEnum::Unmuted,
        channelName: 'coding-together',
        channelId: '444555666',
        username: 'dev123',
    );

    resolve(NewVoiceMessage::class)->persist($dto);

    $voice = Voice::query()->latest()->first();
    expect($voice)->not->toBeNull()
        ->and($voice->channel_name)->toBe('coding-together')
        ->and($voice->channel_id)->toBe('444555666')
        ->and($voice->occurred_at)->not->toBeNull();
});
