<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

test('a Twitch é a única plataforma de streaming por enquanto', function (): void {
    expect(IdentityProvider::streamingPlatforms())->toBe([IdentityProvider::Twitch]);
});

test('só as plataformas de streaming se declaram como tal', function (IdentityProvider $provider, bool $isStreamingPlatform): void {
    expect($provider->isStreamingPlatform())->toBe($isStreamingPlatform);
})->with([
    'twitch' => [IdentityProvider::Twitch, true],
    'discord' => [IdentityProvider::Discord, false],
    'github' => [IdentityProvider::GitHub, false],
    'youtube, até existir a integração' => [IdentityProvider::YouTube, false],
]);
