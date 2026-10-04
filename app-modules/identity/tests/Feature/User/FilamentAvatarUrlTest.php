<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Enums\ProfileImage;
use He4rt\Identity\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** @param array<string, mixed> $attributes */
function linkAvatarIdentity(User $user, IdentityProvider $provider, array $attributes = []): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => $provider,
        ...$attributes,
    ]);
}

test('uploaded avatar wins over linked accounts', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 500, 500))->toMediaCollection('avatar');
    linkAvatarIdentity($user, IdentityProvider::GitHub, ['metadata' => ['username' => 'octocat']]);

    expect($user->fresh()->getFilamentAvatarUrl())->toBe($user->imageUrl(ProfileImage::Avatar));
});

test('linked github account wins over discord', function (): void {
    $user = User::factory()->create(['username' => 'site-handle']);
    linkAvatarIdentity($user, IdentityProvider::GitHub, ['metadata' => ['username' => 'octocat']]);
    linkAvatarIdentity($user, IdentityProvider::Discord, ['metadata' => ['avatar' => 'https://cdn.discordapp.com/avatars/1/a.png']]);

    expect($user->getFilamentAvatarUrl())->toBe('https://github.com/octocat.png');
});

test('github avatar stored by oauth wins over the username url', function (): void {
    $user = User::factory()->create();
    linkAvatarIdentity($user, IdentityProvider::GitHub, ['metadata' => [
        'username' => 'octocat',
        'avatar' => 'https://avatars.githubusercontent.com/u/583231?v=4',
    ]]);

    expect($user->getFilamentAvatarUrl())->toBe('https://avatars.githubusercontent.com/u/583231?v=4');
});

test('discord avatar url is used as stored', function (): void {
    $user = User::factory()->create();
    linkAvatarIdentity($user, IdentityProvider::Discord, ['metadata' => ['avatar' => 'https://cdn.discordapp.com/avatars/1/a.png']]);

    expect($user->getFilamentAvatarUrl())->toBe('https://cdn.discordapp.com/avatars/1/a.png');
});

test('discord avatar hash becomes a cdn url', function (): void {
    $user = User::factory()->create();
    linkAvatarIdentity($user, IdentityProvider::Discord, [
        'external_account_id' => '286313989237899276',
        'metadata' => ['avatar' => 'abc123'],
    ]);

    expect($user->getFilamentAvatarUrl())->toBe('https://cdn.discordapp.com/avatars/286313989237899276/abc123.png');
});

test('disconnected accounts are ignored', function (): void {
    $user = User::factory()->create();
    linkAvatarIdentity($user, IdentityProvider::GitHub, [
        'metadata' => ['username' => 'octocat'],
        'disconnected_at' => now(),
    ]);

    expect($user->getFilamentAvatarUrl())->toBeNull();
});

test('site username alone never builds a github url', function (): void {
    $user = User::factory()->create(['username' => 'discord-handle']);

    expect($user->getFilamentAvatarUrl())->toBeNull();
});
