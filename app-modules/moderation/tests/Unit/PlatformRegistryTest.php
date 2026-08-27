<?php

declare(strict_types=1);

use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\ModerationPlatformContract;
use He4rt\Moderation\Platform\PlatformRegistry;
use He4rt\Moderation\Platform\WebModerationAdapter;

test('register and resolve returns the correct adapter', function (): void {
    $registry = new PlatformRegistry();
    $registry->register(Platform::Web, WebModerationAdapter::class);

    $adapter = $registry->resolve(Platform::Web);

    expect($adapter)->toBeInstanceOf(ModerationPlatformContract::class)
        ->and($adapter)->toBeInstanceOf(WebModerationAdapter::class);
});

test('resolve throws RuntimeException for unregistered platform', function (): void {
    $registry = new PlatformRegistry();

    $registry->resolve(Platform::Discord);
})->throws(RuntimeException::class, 'No adapter registered for platform: discord');

test('has returns true for registered platform', function (): void {
    $registry = new PlatformRegistry();
    $registry->register(Platform::Web, WebModerationAdapter::class);

    expect($registry->has(Platform::Web))->toBeTrue();
});

test('has returns false for unregistered platform', function (): void {
    $registry = new PlatformRegistry();

    expect($registry->has(Platform::Discord))->toBeFalse();
});
