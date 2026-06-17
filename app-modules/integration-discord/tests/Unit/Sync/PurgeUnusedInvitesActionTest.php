<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Sync\Actions\PurgeUnusedInvitesAction;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Invites\DeleteInvite;
use He4rt\IntegrationDiscord\Transport\Requests\Invites\ListGuildInvites;
use Illuminate\Support\Sleep;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function makeInvite(string $code, int $maxAge, int $uses, string $channel = 'general', string $inviter = 'alice'): array
{
    return [
        'code' => $code,
        'max_age' => $maxAge,
        'uses' => $uses,
        'max_uses' => 0,
        'channel' => ['name' => $channel],
        'inviter' => ['username' => $inviter],
        'created_at' => '2025-01-15T10:00:00+00:00',
    ];
}

it('identifies unused infinite invites in dry-run mode', function (): void {
    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
        makeInvite('bbb', maxAge: 3_600, uses: 0),
        makeInvite('ccc', maxAge: 0, uses: 5),
        makeInvite('ddd', maxAge: 0, uses: 0, channel: 'dev-chat', inviter: 'bob'),
    ];

    $mockClient = new MockClient([
        ListGuildInvites::class => MockResponse::make($invites),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: true);

    expect($result->total)->toBe(4)
        ->and($result->matched)->toBe(2)
        ->and($result->deleted)->toBe(0)
        ->and($result->failed)->toBe(0)
        ->and($result->invites)->toHaveCount(2)
        ->and($result->invites[0]->code)->toBe('aaa')
        ->and($result->invites[1]->code)->toBe('ddd')
        ->and($result->invites[1]->channel)->toBe('dev-chat')
        ->and($result->invites[1]->inviter)->toBe('bob');

    $mockClient->assertNotSent(DeleteInvite::class);
});

it('deletes matching invites in live mode', function (): void {
    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
        makeInvite('bbb', maxAge: 3_600, uses: 0),
        makeInvite('ccc', maxAge: 0, uses: 0),
    ];

    $mockClient = new MockClient([
        ListGuildInvites::class => MockResponse::make($invites),
        DeleteInvite::class => MockResponse::make([], 204),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->total)->toBe(3)
        ->and($result->matched)->toBe(2)
        ->and($result->deleted)->toBe(2)
        ->and($result->failed)->toBe(0);

    $mockClient->assertSentCount(3);
});

it('returns zero matches when no invites qualify', function (): void {
    $invites = [
        makeInvite('aaa', maxAge: 3_600, uses: 0),
        makeInvite('bbb', maxAge: 0, uses: 3),
        makeInvite('ccc', maxAge: 86_400, uses: 10),
    ];

    $mockClient = new MockClient([
        ListGuildInvites::class => MockResponse::make($invites),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->total)->toBe(3)
        ->and($result->matched)->toBe(0)
        ->and($result->deleted)->toBe(0)
        ->and($result->invites)->toBeEmpty();
});

it('counts failures individually without aborting', function (): void {
    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
        makeInvite('bbb', maxAge: 0, uses: 0),
        makeInvite('ccc', maxAge: 0, uses: 0),
    ];

    $mockClient = new MockClient([
        MockResponse::make($invites),
        MockResponse::make([], 204),
        MockResponse::make(['message' => 'Unknown Invite'], 404),
        MockResponse::make([], 204),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->matched)->toBe(3)
        ->and($result->deleted)->toBe(2)
        ->and($result->failed)->toBe(1);
});

it('includes expiring invites when flag is set', function (): void {
    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
        makeInvite('bbb', maxAge: 3_600, uses: 0),
        makeInvite('ccc', maxAge: 86_400, uses: 0),
        makeInvite('ddd', maxAge: 0, uses: 3),
        makeInvite('eee', maxAge: 3_600, uses: 2),
    ];

    $mockClient = new MockClient([
        ListGuildInvites::class => MockResponse::make($invites),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: true, includeExpiring: true);

    expect($result->total)->toBe(5)
        ->and($result->matched)->toBe(3)
        ->and($result->invites[0]->code)->toBe('aaa')
        ->and($result->invites[1]->code)->toBe('bbb')
        ->and($result->invites[2]->code)->toBe('ccc');

    $mockClient->assertNotSent(DeleteInvite::class);
});

it('throws when list invites response fails', function (): void {
    $mockClient = new MockClient([
        ListGuildInvites::class => MockResponse::make(['message' => 'Missing Permissions'], 403),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $action->execute('guild-123');
})->throws(RuntimeException::class, 'Failed to list guild invites: HTTP 403');

it('retries on 429 rate limit and succeeds', function (): void {
    Sleep::fake();

    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
    ];

    $mockClient = new MockClient([
        MockResponse::make($invites),
        MockResponse::make(['message' => 'You are being rate limited.', 'retry_after' => 0.3], 429),
        MockResponse::make([], 204),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->deleted)->toBe(1)
        ->and($result->failed)->toBe(0);

    Sleep::assertSleptTimes(1);
});

it('uses X-RateLimit-Reset-After header when body has no retry_after', function (): void {
    Sleep::fake();

    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
    ];

    $mockClient = new MockClient([
        MockResponse::make($invites),
        MockResponse::make(['message' => 'You are being rate limited.'], 429, ['X-RateLimit-Reset-After' => '2.57']),
        MockResponse::make([], 204),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->deleted)->toBe(1)
        ->and($result->failed)->toBe(0);
});

it('retries on cloudflare 429 with non-json body and short retry-after header', function (): void {
    Sleep::fake();

    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
    ];

    $mockClient = new MockClient([
        MockResponse::make($invites),
        MockResponse::make('error code: 1015', 429, ['Retry-After' => '5']),
        MockResponse::make([], 204),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->deleted)->toBe(1)
        ->and($result->failed)->toBe(0);
});

it('aborts entire purge on cloudflare ip ban', function (): void {
    Sleep::fake();

    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
        makeInvite('bbb', maxAge: 0, uses: 0),
        makeInvite('ccc', maxAge: 0, uses: 0),
    ];

    $mockClient = new MockClient([
        MockResponse::make($invites),
        MockResponse::make([], 204),
        MockResponse::make('error code: 1015', 429, ['Retry-After' => '80974']),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->deleted)->toBe(1)
        ->and($result->failed)->toBe(2);

    $mockClient->assertSentCount(3);
    Sleep::assertSleptTimes(1);
});

it('fails after exhausting retries on persistent 429', function (): void {
    Sleep::fake();

    $invites = [
        makeInvite('aaa', maxAge: 0, uses: 0),
    ];

    $mockClient = new MockClient([
        MockResponse::make($invites),
        MockResponse::make(['message' => 'You are being rate limited.', 'retry_after' => 0.3], 429),
        MockResponse::make(['message' => 'You are being rate limited.', 'retry_after' => 0.3], 429),
        MockResponse::make(['message' => 'You are being rate limited.', 'retry_after' => 0.3], 429),
        MockResponse::make(['message' => 'You are being rate limited.', 'retry_after' => 0.3], 429),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123', dryRun: false);

    expect($result->deleted)->toBe(0)
        ->and($result->failed)->toBe(1);
});

it('handles an empty invite list', function (): void {
    $mockClient = new MockClient([
        ListGuildInvites::class => MockResponse::make([]),
    ]);

    $connector = new DiscordConnector('test-token');
    $connector->withMockClient($mockClient);

    $action = new PurgeUnusedInvitesAction($connector);
    $result = $action->execute('guild-123');

    expect($result->total)->toBe(0)
        ->and($result->matched)->toBe(0)
        ->and($result->invites)->toBeEmpty();
});
