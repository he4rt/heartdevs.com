<?php

declare(strict_types=1);

use He4rt\Activity\Voice\Models\Voice;
use He4rt\Activity\Voice\Queries\GetCurrentVoiceChannel;
use He4rt\Activity\Voice\ValueObjects\CurrentVoiceChannel;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{tenant: Tenant, identity: ExternalIdentity}
 */
function seedVoiceIdentity(string $externalAccountId = '123456'): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $identity = ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $externalAccountId,
        ]);

    return ['tenant' => $tenant, 'identity' => $identity];
}

function recordVoice(Tenant $tenant, ExternalIdentity $identity, string $state, ?string $channelId, ?string $channelName = null): void
{
    $factory = match ($state) {
        'joined' => Voice::factory()->joined(),
        'left' => Voice::factory()->left(),
        default => Voice::factory()->state(['state' => $state]), // xp-ticker samples (muted/unmuted/disabled)
    };

    $factory->create([
        'tenant_id' => $tenant->id,
        'external_identity_id' => $identity->id,
        'channel_id' => $channelId,
        'channel_name' => $channelName ?? 'channel',
    ]);
}

test('returns null when the user has no voice rows', function (): void {
    ['tenant' => $tenant] = seedVoiceIdentity();

    $current = resolve(GetCurrentVoiceChannel::class)->handle(
        $tenant->id,
        IdentityProvider::Discord,
        '123456',
    );

    expect($current)->toBeNull();
});

test('returns the channel when the latest row is a join', function (): void {
    ['tenant' => $tenant, 'identity' => $identity] = seedVoiceIdentity();
    recordVoice($tenant, $identity, 'joined', 'chA', 'General');

    $current = resolve(GetCurrentVoiceChannel::class)->handle($tenant->id, IdentityProvider::Discord, '123456');

    expect($current)->toBeInstanceOf(CurrentVoiceChannel::class)
        ->and($current->channelId)->toBe('chA')
        ->and($current->channelName)->toBe('General');
});

test('returns null when the latest row is a leave', function (): void {
    ['tenant' => $tenant, 'identity' => $identity] = seedVoiceIdentity();
    recordVoice($tenant, $identity, 'joined', 'chA');
    recordVoice($tenant, $identity, 'left', 'chA');

    $current = resolve(GetCurrentVoiceChannel::class)->handle($tenant->id, IdentityProvider::Discord, '123456');

    expect($current)->toBeNull();
});

test('treats an xp ticker row (unmuted) as present in that channel', function (): void {
    ['tenant' => $tenant, 'identity' => $identity] = seedVoiceIdentity();
    recordVoice($tenant, $identity, 'joined', 'chA');
    recordVoice($tenant, $identity, 'unmuted', 'chA', 'General');

    $current = resolve(GetCurrentVoiceChannel::class)->handle($tenant->id, IdentityProvider::Discord, '123456');

    expect($current?->channelId)->toBe('chA');
});

test('follows a move to the most recent channel', function (): void {
    ['tenant' => $tenant, 'identity' => $identity] = seedVoiceIdentity();
    recordVoice($tenant, $identity, 'joined', 'chA');
    recordVoice($tenant, $identity, 'left', 'chA');
    recordVoice($tenant, $identity, 'joined', 'chB', 'Dev');

    $current = resolve(GetCurrentVoiceChannel::class)->handle($tenant->id, IdentityProvider::Discord, '123456');

    expect($current?->channelId)->toBe('chB')
        ->and($current?->channelName)->toBe('Dev');
});

test('is scoped to the requested user', function (): void {
    ['tenant' => $tenant, 'identity' => $identity] = seedVoiceIdentity('aaa');
    recordVoice($tenant, $identity, 'joined', 'chA');

    $current = resolve(GetCurrentVoiceChannel::class)->handle($tenant->id, IdentityProvider::Discord, 'bbb');

    expect($current)->toBeNull();
});
