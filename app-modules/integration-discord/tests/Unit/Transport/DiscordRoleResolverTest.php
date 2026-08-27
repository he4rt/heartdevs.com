<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\DiscordRoleResolver;
use He4rt\IntegrationDiscord\Transport\Requests\Members\GetMember;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['admin-role-1', 'admin-role-2']);
    config()->set('he4rt.discord.moderation.mod_role_ids', ['mod-role-1']);
});

it('returns admin when member has an admin role', function (): void {
    $mockClient = new MockClient([
        GetMember::class => MockResponse::make([
            'roles' => ['some-role', 'admin-role-1', 'other-role'],
        ]),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $resolver = new DiscordRoleResolver($connector);

    expect($resolver->resolveProtectionTier('guild-123', 'user-456'))->toBe('admin');
});

it('returns mod when member has a mod role', function (): void {
    $mockClient = new MockClient([
        GetMember::class => MockResponse::make([
            'roles' => ['some-role', 'mod-role-1'],
        ]),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $resolver = new DiscordRoleResolver($connector);

    expect($resolver->resolveProtectionTier('guild-123', 'user-456'))->toBe('mod');
});

it('returns null when member has no protected roles', function (): void {
    $mockClient = new MockClient([
        GetMember::class => MockResponse::make([
            'roles' => ['some-role', 'other-role'],
        ]),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $resolver = new DiscordRoleResolver($connector);

    expect($resolver->resolveProtectionTier('guild-123', 'user-456'))->toBeNull();
});

it('returns null when api fails with non-200 response', function (): void {
    $mockClient = new MockClient([
        GetMember::class => MockResponse::make(['message' => 'Unknown Member'], 404),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $resolver = new DiscordRoleResolver($connector);

    expect($resolver->resolveProtectionTier('guild-123', 'user-456'))->toBeNull();
});

it('prioritizes admin over mod when member has both roles', function (): void {
    $mockClient = new MockClient([
        GetMember::class => MockResponse::make([
            'roles' => ['admin-role-2', 'mod-role-1'],
        ]),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $resolver = new DiscordRoleResolver($connector);

    expect($resolver->resolveProtectionTier('guild-123', 'user-456'))->toBe('admin');
});
