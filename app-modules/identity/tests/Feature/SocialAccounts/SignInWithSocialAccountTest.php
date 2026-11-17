<?php

declare(strict_types=1);

use He4rt\Identity\SocialAccounts\Actions\SignInWithSocialAccount;
use He4rt\Identity\SocialAccounts\DTOs\SocialUser;
use He4rt\Identity\SocialAccounts\Exceptions\RegistrationClosed;
use He4rt\Identity\SocialAccounts\Exceptions\SocialEmailMissing;
use He4rt\Identity\SocialAccounts\Exceptions\SocialEmailNotVerified;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;

function socialUser(?string $email = 'ana@example.test', bool $emailVerified = true, string $id = '583231'): SocialUser
{
    return new SocialUser(providerUserId: $id, name: 'Ana Lima', email: $email, emailVerified: $emailVerified);
}

function signIn(SocialUser $socialUser, bool $canRegister = true, SocialProvider $provider = SocialProvider::GitHub): User
{
    return resolve(SignInWithSocialAccount::class)->handle($provider, $socialUser, $canRegister);
}

it('signs in the user that the provider account is linked to', function (): void {
    $account = SocialAccount::factory()->create(['provider_user_id' => '583231']);

    $user = signIn(socialUser(email: 'another@example.test'));

    expect($user->is($account->user))->toBeTrue()
        ->and(User::query()->count())->toBe(1)
        ->and(SocialAccount::query()->count())->toBe(1);
});

it('links the account to the user with the same verified email', function (): void {
    $existing = User::factory()->create(['email' => 'ana@example.test']);

    $user = signIn(socialUser(email: 'Ana@Example.test'));

    expect($user->is($existing))->toBeTrue()
        ->and($existing->socialAccounts()->sole())
        ->provider->toBe(SocialProvider::GitHub)
        ->provider_user_id->toBe('583231');
});

it('never links an email that the provider did not verify', function (): void {
    User::factory()->create(['email' => 'ana@example.test']);

    expect(fn (): User => signIn(socialUser(emailVerified: false), provider: SocialProvider::Discord))
        ->toThrow(SocialEmailNotVerified::class, 'Log in and connect Discord in Settings.');

    expect(SocialAccount::query()->exists())->toBeFalse();
});

it('creates a user without a password while registration is open', function (bool $emailVerified): void {
    $user = signIn(socialUser(email: 'bia@example.test', emailVerified: $emailVerified));

    expect($user)
        ->email->toBe('bia@example.test')
        ->name->toBe('Ana Lima')
        ->hasPassword()->toBeFalse()
        ->hasVerifiedEmail()->toBe($emailVerified)
        ->and($user->socialAccounts()->sole()->provider_user_id)->toBe('583231');
})->with(['verified email' => [true], 'unverified email' => [false]]);

it('creates no user while registration is closed', function (): void {
    expect(fn (): User => signIn(socialUser(), canRegister: false))->toThrow(RegistrationClosed::class);

    expect(User::query()->exists())->toBeFalse();
});

it('refuses an unlinked account without an email', function (): void {
    expect(fn (): User => signIn(socialUser(email: null)))->toThrow(SocialEmailMissing::class);

    expect(User::query()->exists())->toBeFalse();
});
