<?php

declare(strict_types=1);

use Discord\Parts\WebSockets\VoiceStateUpdate;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\BotDiscord\Actions\VoiceChannel\PersistVoiceStateAction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

function makeVoiceState(?string $guildId, ?string $channelId, string $userId): VoiceStateUpdate
{
    $state = Mockery::mock(VoiceStateUpdate::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $state->shouldReceive('getAttribute')->with('guild_id')->andReturn($guildId);
    $state->shouldReceive('getAttribute')->with('channel_id')->andReturn($channelId);
    $state->shouldReceive('getAttribute')->with('user_id')->andReturn($userId);

    return $state;
}

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->discordUserId = '123456789';
    $this->guildId = '987654321';
    $this->channelId = '111222333';

    ExternalIdentity::factory()->create([
        'tenant_id' => $this->tenant->id,
        'model_type' => (new Tenant)->getMorphClass(),
        'model_id' => $this->tenant->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => $this->guildId,
    ]);

    ExternalIdentity::factory()->create([
        'tenant_id' => $this->tenant->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $this->user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => $this->discordUserId,
    ]);
});

test('persists a joined event when user connects to a channel', function (): void {
    $state = makeVoiceState($this->guildId, $this->channelId, $this->discordUserId);
    $oldState = makeVoiceState($this->guildId, null, $this->discordUserId);

    (new PersistVoiceStateAction)->execute($state, $oldState);

    $voice = Voice::query()->latest()->first();

    expect($voice)->not->toBeNull()
        ->and($voice->state)->toBe('joined')
        ->and($voice->channel_name)->toBe($this->channelId)
        ->and($voice->tenant_id)->toBe($this->tenant->id)
        ->and($voice->obtained_experience)->toBe(0);
});

test('persists a joined event when oldState is null', function (): void {
    $state = makeVoiceState($this->guildId, $this->channelId, $this->discordUserId);

    (new PersistVoiceStateAction)->execute($state, null);

    expect(Voice::query()->count())->toBe(1)
        ->and(Voice::query()->first()->state)->toBe('joined');
});

test('persists a left event when user disconnects', function (): void {
    $state = makeVoiceState($this->guildId, null, $this->discordUserId);
    $oldState = makeVoiceState($this->guildId, $this->channelId, $this->discordUserId);

    (new PersistVoiceStateAction)->execute($state, $oldState);

    $voice = Voice::query()->latest()->first();

    expect($voice)->not->toBeNull()
        ->and($voice->state)->toBe('left')
        ->and($voice->channel_name)->toBe($this->channelId);
});

test('persists two events when user switches channels', function (): void {
    $newChannelId = '444555666';
    $state = makeVoiceState($this->guildId, $newChannelId, $this->discordUserId);
    $oldState = makeVoiceState($this->guildId, $this->channelId, $this->discordUserId);

    (new PersistVoiceStateAction)->execute($state, $oldState);

    $voices = Voice::query()->orderBy('id')->get();

    expect($voices)->toHaveCount(2)
        ->and($voices[0]->state)->toBe('left')
        ->and($voices[0]->channel_name)->toBe($this->channelId)
        ->and($voices[1]->state)->toBe('joined')
        ->and($voices[1]->channel_name)->toBe($newChannelId);
});

test('skips persistence for mute toggle in same channel', function (): void {
    $state = makeVoiceState($this->guildId, $this->channelId, $this->discordUserId);
    $oldState = makeVoiceState($this->guildId, $this->channelId, $this->discordUserId);

    (new PersistVoiceStateAction)->execute($state, $oldState);

    expect(Voice::query()->count())->toBe(0);
});

test('skips persistence when tenant is not found', function (): void {
    $state = makeVoiceState('unknown-guild', $this->channelId, $this->discordUserId);

    (new PersistVoiceStateAction)->execute($state, null);

    expect(Voice::query()->count())->toBe(0);
});

test('skips persistence when user identity is not found', function (): void {
    $state = makeVoiceState($this->guildId, $this->channelId, 'unknown-user');

    (new PersistVoiceStateAction)->execute($state, null);

    expect(Voice::query()->count())->toBe(0);
});
