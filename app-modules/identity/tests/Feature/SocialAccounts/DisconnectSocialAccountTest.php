<?php

declare(strict_types=1);

use He4rt\Identity\SocialAccounts\Actions\DisconnectSocialAccount;
use He4rt\Identity\SocialAccounts\Exceptions\LastSignInMethod;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Actions\DeleteUser;
use He4rt\Identity\Users\User;

it('disconnects an account while another sign-in method is left', function (string $method): void {
    $user = match ($method) {
        'password' => User::factory()->create(),
        'another account' => tap(User::factory()->withoutPassword()->create(), fn (User $user) => SocialAccount::factory()->for($user)->provider(SocialProvider::Google)->create()),
    };
    $account = SocialAccount::factory()->for($user)->create();

    resolve(DisconnectSocialAccount::class)->handle($account);

    expect(SocialAccount::query()->whereKey($account->id)->exists())->toBeFalse();
})->with(['password', 'another account']);

it('keeps the last way to log in', function (): void {
    $account = SocialAccount::factory()->for(User::factory()->withoutPassword())->create();

    expect(fn () => resolve(DisconnectSocialAccount::class)->handle($account))->toThrow(LastSignInMethod::class);

    expect(SocialAccount::query()->whereKey($account->id)->exists())->toBeTrue();
});

it('deletes the social accounts with the user', function (): void {
    $user = User::factory()->create();
    SocialAccount::factory()->for($user)->create();
    SocialAccount::factory()->for($user)->provider(SocialProvider::Twitch)->create();

    resolve(DeleteUser::class)->handle($user);

    expect(SocialAccount::query()->exists())->toBeFalse();
});
