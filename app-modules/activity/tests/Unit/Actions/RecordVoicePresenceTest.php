<?php

declare(strict_types=1);

use He4rt\Activity\Voice\Actions\RecordVoicePresence;
use He4rt\Activity\Voice\DTOs\RecordVoicePresenceDTO;
use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{tenant: Tenant, character: Character}
 */
function seedPresenceUser(string $externalAccountId): array
{
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
            'external_account_id' => $externalAccountId,
        ]);

    return ['tenant' => $tenant, 'character' => $character];
}

test('joined presence is recorded with zero xp and leaves character xp untouched', function (): void {
    ['tenant' => $tenant, 'character' => $character] = seedPresenceUser('123456');

    resolve(RecordVoicePresence::class)->persist(new RecordVoicePresenceDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '123456',
        presence: VoicePresenceEnum::Joined,
        channelName: 'general-voice',
        channelId: '111222333',
        username: 'testuser',
    ));

    expect($character->fresh()->experience)->toBe(4_500);

    $voice = Voice::query()->latest('id')->first();
    expect($voice)->not->toBeNull()
        ->and($voice->state)->toBe('joined')
        ->and($voice->obtained_experience)->toBe(0)
        ->and($voice->channel_name)->toBe('general-voice')
        ->and($voice->channel_id)->toBe('111222333')
        ->and($voice->tenant_id)->toBe($tenant->id)
        ->and($voice->occurred_at)->not->toBeNull();
});

test('left presence is recorded with zero xp', function (): void {
    ['tenant' => $tenant] = seedPresenceUser('789');

    resolve(RecordVoicePresence::class)->persist(new RecordVoicePresenceDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '789',
        presence: VoicePresenceEnum::Left,
        channelName: 'general-voice',
    ));

    $voice = Voice::query()->latest('id')->first();
    expect($voice)->not->toBeNull()
        ->and($voice->state)->toBe('left')
        ->and($voice->obtained_experience)->toBe(0);
});

test('presence is audio-agnostic: a record is written regardless of mute or deaf state', function (): void {
    // The DTO carries no audio state, so a deafened user joining is recorded
    // exactly like anyone else — proven by the row simply existing.
    ['tenant' => $tenant] = seedPresenceUser('456');

    resolve(RecordVoicePresence::class)->persist(new RecordVoicePresenceDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '456',
        presence: VoicePresenceEnum::Joined,
        channelName: 'general-voice',
        channelId: '999888777',
    ));

    expect(Voice::query()->where('state', 'joined')->count())->toBe(1);
});
