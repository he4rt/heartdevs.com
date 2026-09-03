<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

it('builds a profile url for the providers that have one', function (IdentityProvider $provider, string $expected): void {
    expect($provider->profileUrl('danielhe4rt'))->toBe($expected);
})->with([
    'github' => [IdentityProvider::GitHub, 'https://github.com/danielhe4rt'],
    'twitch' => [IdentityProvider::Twitch, 'https://twitch.tv/danielhe4rt'],
]);

it('returns null for discord, which has no public profile page', function (): void {
    expect(IdentityProvider::Discord->profileUrl('danielhe4rt'))->toBeNull();
});

it('returns null for providers outside the supported set', function (): void {
    expect(IdentityProvider::Steam->profileUrl('danielhe4rt'))->toBeNull()
        ->and(IdentityProvider::Spotify->profileUrl('danielhe4rt'))->toBeNull();
});

it('strips a leading @ and surrounding spaces from the handle', function (string $handle): void {
    expect(IdentityProvider::GitHub->profileUrl($handle))->toBe('https://github.com/danielhe4rt');
})->with([
    'plain' => ['danielhe4rt'],
    'at prefixed' => ['@danielhe4rt'],
    'padded' => ['  danielhe4rt  '],
    'at prefixed and padded' => [' @danielhe4rt '],
]);

it('passes an already absolute url through untouched', function (): void {
    expect(IdentityProvider::GitHub->profileUrl('https://github.com/danielhe4rt'))
        ->toBe('https://github.com/danielhe4rt');
});

it('returns null for an empty handle', function (?string $handle): void {
    expect(IdentityProvider::GitHub->profileUrl($handle))->toBeNull();
})->with([
    'null' => [null],
    'empty' => [''],
    'only spaces' => ['   '],
]);

it('gives every supported provider a decided answer', function (): void {
    $decided = [
        IdentityProvider::GitHub->value => 'https://github.com/handle',
        IdentityProvider::Twitch->value => 'https://twitch.tv/handle',
        IdentityProvider::DevTo->value => 'https://dev.to/handle',
        IdentityProvider::Discord->value => null,
    ];

    foreach (IdentityProvider::supportedProviders() as $provider) {
        expect($decided)->toHaveKey($provider->value)
            ->and($provider->profileUrl('handle'))->toBe($decided[$provider->value]);
    }
});
