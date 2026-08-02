<?php

declare(strict_types=1);

use He4rt\Profile\Enums\SocialPlatform;

dataset('username handles', [
    'plain username' => ['johndoe'],
    'username with @' => ['@johndoe'],
]);

test('instagram builds correct url from handle', function (string $handle): void {
    expect(SocialPlatform::Instagram->getUrl($handle))
        ->toBe('https://instagram.com/johndoe');
})->with('username handles');

test('twitter builds correct url from handle', function (string $handle): void {
    expect(SocialPlatform::Twitter->getUrl($handle))
        ->toBe('https://x.com/johndoe');
})->with('username handles');

test('linkedin builds correct url from handle', function (string $handle): void {
    expect(SocialPlatform::LinkedIn->getUrl($handle))
        ->toBe('https://linkedin.com/in/johndoe');
})->with('username handles');

test('youtube builds correct url from handle', function (string $handle): void {
    expect(SocialPlatform::YouTube->getUrl($handle))
        ->toBe('https://youtube.com/@johndoe');
})->with('username handles');

test('bluesky builds correct url from handle', function (string $handle): void {
    expect(SocialPlatform::Bluesky->getUrl($handle))
        ->toBe('https://bsky.app/profile/johndoe');
})->with('username handles');

test('website prepends https when no scheme is provided', function (): void {
    expect(SocialPlatform::Website->getUrl('example.dev'))
        ->toBe('https://example.dev');
});

test('full https url is returned as-is for any platform', function (): void {
    $url = 'https://www.linkedin.com/in/johndoe/';

    expect(SocialPlatform::LinkedIn->getUrl($url))->toBe($url);
});

test('full http url is returned as-is for any platform', function (): void {
    $url = 'http://example.dev';

    expect(SocialPlatform::Website->getUrl($url))->toBe($url);
});

test('full url is not double-prefixed for all platforms', function (): void {
    foreach (SocialPlatform::cases() as $platform) {
        $url = 'https://example.com/foo';
        expect($platform->getUrl($url))->toBe($url);
    }
});
