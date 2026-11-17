<?php

declare(strict_types=1);

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;

it('starts with every method off outside production', function (): void {
    $settings = resolve(AuthenticationSettings::class);

    expect($settings->twoFactorEnabled)->toBeFalse()
        ->and($settings->passkeysEnabled)->toBeFalse();
});

it('challenges and requires two factor only while two factor is on', function (bool $twoFactorEnabled, bool $userHasTwoFactor, bool $challenges, bool $requiresSetup): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => $twoFactorEnabled, 'passkeysEnabled' => false]);
    $factory = User::factory();
    $user = ($userHasTwoFactor ? $factory->withTwoFactor() : $factory)->create();

    $settings = resolve(AuthenticationSettings::class);

    expect($settings->challengesTwoFactor($user))->toBe($challenges)
        ->and($settings->requiresTwoFactorSetup($user))->toBe($requiresSetup);
})->with([
    'on, user has it' => [true, true, true, false],
    'on, user has none' => [true, false, false, true],
    'off, user has it' => [false, true, false, false],
    'off, user has none' => [false, false, false, false],
]);
