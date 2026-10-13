<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Identity\DiscordUserSnapshot;

test('distinguishes a missing avatar from an explicit null avatar', function (): void {
    $missing = DiscordUserSnapshot::fromArray(['username' => 'letsch']);
    $explicitNull = DiscordUserSnapshot::fromArray(['username' => 'letsch', 'avatar' => null]);

    expect($missing->hasAvatar)->toBeFalse()
        ->and($missing->toArray())->not->toHaveKey('avatar')
        ->and($explicitNull->hasAvatar)->toBeTrue()
        ->and($explicitNull->toArray())->toHaveKey('avatar', value: null);
});

test('keeps stored values and fills only the missing ones from the fallback', function (): void {
    $stored = DiscordUserSnapshot::fromArray(['username' => null, 'global_name' => 'Stored', 'avatar' => null]);
    $fallback = new DiscordUserSnapshot('incoming', 'Incoming', 'hash', hasAvatar: true);

    expect($stored->fillMissingFrom($fallback)->toArray())->toBe([
        'username' => 'incoming',
        'global_name' => 'Stored',
        'avatar' => null,
    ]);
});
