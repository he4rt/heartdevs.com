<?php

declare(strict_types=1);

use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\Rules\ConfiguredSocialProvider;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use Illuminate\Support\Facades\Validator;

beforeEach(function (): void {
    config([
        'services.github' => ['client_id' => 'id', 'client_secret' => 'secret'],
        'services.google' => ['client_id' => 'id', 'client_secret' => null],
        'services.discord' => ['client_id' => 'id', 'client_secret' => 'secret'],
        'services.twitch' => null,
    ]);
    AuthenticationSettings::fake(['socialProviders' => ['github', 'google']]);
});

it('lists the providers with a client id and a secret', function (): void {
    expect(resolve(AvailableSocialProviders::class)->configured())->toBe([SocialProvider::GitHub, SocialProvider::Discord]);
});

it('enables only the providers that are on and configured', function (): void {
    $providers = resolve(AvailableSocialProviders::class);

    expect($providers->enabled())->toBe([SocialProvider::GitHub])
        ->and($providers->isEnabled(SocialProvider::Google))->toBeFalse();
});

it('accepts only a configured provider', function (mixed $value, bool $passes): void {
    $validator = Validator::make(['provider' => $value], ['provider' => [new ConfiguredSocialProvider(resolve(AvailableSocialProviders::class))]]);

    expect($validator->passes())->toBe($passes);
})->with([
    'configured' => ['github', true],
    'without a secret' => ['google', false],
    'without config' => ['twitch', false],
    'not a provider, left to the enum rule' => ['gitlab', true],
    'not a string, left to the enum rule' => [42, true],
]);

it('starts with no social provider on', function (): void {
    AuthenticationSettings::fake([], loadMissingValues: true);

    expect(resolve(AuthenticationSettings::class)->socialProviders)->toBeEmpty();
});
