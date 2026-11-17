<?php

declare(strict_types=1);

use He4rt\Identity\SocialAccounts\Actions\ConnectSocialAccount;
use He4rt\Identity\SocialAccounts\DTOs\SocialUser;
use He4rt\Identity\SocialAccounts\Exceptions\SocialAccountTaken;
use He4rt\Identity\SocialAccounts\Exceptions\SocialProviderAlreadyConnected;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;

function connect(User $user, string $providerUserId = '583231'): SocialAccount
{
    $socialUser = new SocialUser(providerUserId: $providerUserId, name: 'Ana', email: 'ana@example.test', emailVerified: true);

    return resolve(ConnectSocialAccount::class)->handle($user, SocialProvider::GitHub, $socialUser);
}

it('links a provider account to the user', function (): void {
    $user = User::factory()->create();

    $account = connect($user);

    expect($account->user->is($user))->toBeTrue()
        ->and($account->provider)->toBe(SocialProvider::GitHub);
});

it('returns the link that already exists for the same user', function (): void {
    $account = SocialAccount::factory()->create(['provider_user_id' => '583231']);

    expect(connect($account->user)->is($account))->toBeTrue()
        ->and(SocialAccount::query()->count())->toBe(1);
});

it('refuses an account that another user owns', function (): void {
    SocialAccount::factory()->create(['provider_user_id' => '583231']);

    expect(fn (): SocialAccount => connect(User::factory()->create()))
        ->toThrow(SocialAccountTaken::class, 'This GitHub account is linked to another user.');
});

it('refuses a second account of the same provider', function (): void {
    $account = SocialAccount::factory()->create(['provider_user_id' => '111']);

    expect(fn (): SocialAccount => connect($account->user, '222'))->toThrow(SocialProviderAlreadyConnected::class);

    expect(SocialAccount::query()->count())->toBe(1);
});
