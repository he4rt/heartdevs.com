<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\Auth\Actions\AttachProviderToUser;
use He4rt\Identity\Auth\Actions\FindOrCreateUserByProvider;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

function twitchViewerOAuthUser(string $providerId): OAuthUserDTO
{
    $credentials = new class('token', 'refresh', 3_600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('token', 'refresh', 3_600);
        }
    };

    return new class($credentials, $providerId, IdentityProvider::Twitch, 'mariacoda', 'Maria Coda', email: null, avatarUrl: null) extends OAuthUserDTO
    {
        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials, '', IdentityProvider::Twitch, '', '', null, null);
        }
    };
}

function unclaimedTwitchIdentity(string $providerId): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_id' => null,
        'provider' => IdentityProvider::Twitch,
        'external_account_id' => $providerId,
        'connected_by' => null,
        'connected_at' => null,
        'metadata' => ['username' => 'mariacoda'],
    ]);
}

test('o espectador que entra pela Twitch assume a identidade solta com o histórico', function (): void {
    $unclaimed = unclaimedTwitchIdentity('9911');
    Message::factory()->count(12)->create(['external_identity_id' => $unclaimed->getKey()]);

    $oauthUser = twitchViewerOAuthUser('9911');
    $user = resolve(FindOrCreateUserByProvider::class)->execute($oauthUser);
    $identity = resolve(AttachProviderToUser::class)->execute($user, $oauthUser, $oauthUser->credentials);

    expect($identity->getKey())->toBe($unclaimed->getKey())
        ->and($identity->model_id)->toBe($user->getKey())
        ->and($identity->connected_at)->not->toBeNull()
        ->and(ExternalIdentity::query()->where('provider', IdentityProvider::Twitch)->where('external_account_id', '9911')->count())->toBe(1)
        ->and(Message::query()->where('external_identity_id', $unclaimed->getKey())->count())->toBe(12);
});

test('um membro que conecta a Twitch assume a identidade solta', function (): void {
    $unclaimed = unclaimedTwitchIdentity('9911');
    $member = User::factory()->create();

    $oauthUser = twitchViewerOAuthUser('9911');
    $identity = resolve(AttachProviderToUser::class)->execute($member, $oauthUser, $oauthUser->credentials);

    expect($identity->getKey())->toBe($unclaimed->getKey())
        ->and($identity->model_id)->toBe($member->getKey());
});

test('sem identidade solta, a conexão cria uma identidade nova como antes', function (): void {
    $member = User::factory()->create();

    $oauthUser = twitchViewerOAuthUser('9911');
    $identity = resolve(AttachProviderToUser::class)->execute($member, $oauthUser, $oauthUser->credentials);

    expect($identity->wasRecentlyCreated)->toBeTrue()
        ->and($identity->model_id)->toBe($member->getKey());
});

test('uma identidade que já tem dono não é tomada por outra conexão', function (): void {
    $owner = User::factory()->create();
    $owned = ExternalIdentity::factory()->create([
        'model_id' => $owner->getKey(),
        'provider' => IdentityProvider::Twitch,
        'external_account_id' => '9911',
    ]);

    $oauthUser = twitchViewerOAuthUser('9911');
    $identity = resolve(AttachProviderToUser::class)->execute(User::factory()->create(), $oauthUser, $oauthUser->credentials);

    expect($identity->getKey())->not->toBe($owned->getKey())
        ->and($owned->refresh()->model_id)->toBe($owner->getKey());
});

test('o banco recusa duas identidades soltas da mesma conta', function (): void {
    unclaimedTwitchIdentity('9911');

    unclaimedTwitchIdentity('9911');
})->throws(UniqueConstraintViolationException::class);
