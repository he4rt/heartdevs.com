<?php

declare(strict_types=1);

use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationDiscord\ETL\Adapters\DiscordMessageAdapter;
use He4rt\IntegrationDiscord\ETL\Enums\DiscordMessageType;

function rawMessage(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => '1',
        'type' => 0,
        'author' => ['id' => '100', 'username' => 'alice', 'bot' => false],
        'mentions' => [],
        'mention_roles' => [],
        'mention_everyone' => false,
        'pinned' => false,
        'edited_timestamp' => null,
    ], $overrides);
}

test('IdentityProvider::Discord resolves to DiscordMessageAdapter', function (): void {
    expect(IdentityProvider::Discord->getMessageAdapter())
        ->toBeInstanceOf(DiscordMessageAdapter::class);
});

test('other providers return null adapter', function (): void {
    expect(IdentityProvider::Twitch->getMessageAdapter())->toBeNull()
        ->and(IdentityProvider::DevTo->getMessageAdapter())->toBeNull();
});

dataset('discord_type_to_kind', [
    [0, MessageKind::Default],
    [19, MessageKind::Reply],
    [6, MessageKind::Pin],
    [7, MessageKind::UserJoin],
    [8, MessageKind::Boost],
    [9, MessageKind::Boost],
    [10, MessageKind::Boost],
    [11, MessageKind::Boost],
    [20, MessageKind::Command],
    [23, MessageKind::Command],
    [18, MessageKind::ThreadCreated],
    [21, MessageKind::ThreadStarter],
    [3, MessageKind::Call],
    [24, MessageKind::AutoModeration],
    [4, MessageKind::ChannelUpdate],
    [46, MessageKind::Poll],
    [1, MessageKind::System],
    [22, MessageKind::System],
]);

test('DiscordMessageAdapter maps raw type to canonical MessageKind', function (int $raw, MessageKind $expected): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->messageKind(rawMessage(['type' => $raw])))->toBe($expected);
})->with('discord_type_to_kind');

test('DiscordMessageAdapter returns Unknown when type is missing', function (): void {
    $adapter = new DiscordMessageAdapter;

    $raw = rawMessage();
    unset($raw['type']);

    expect($adapter->messageKind($raw))->toBe(MessageKind::Unknown);
});

test('DiscordMessageAdapter preserves raw discord type', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->rawMessageType(rawMessage(['type' => 19])))->toBe(19);
});

test('DiscordMessageAdapter detects Webhook source', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->sourceKind(rawMessage(['webhook_id' => '999'])))
        ->toBe(MessageSourceKind::Webhook);
});

test('DiscordMessageAdapter detects App source from application_id', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->sourceKind(rawMessage(['application_id' => '777'])))
        ->toBe(MessageSourceKind::App);
});

test('DiscordMessageAdapter detects Bot source from author.bot flag', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->sourceKind(rawMessage(['author' => ['bot' => true]])))
        ->toBe(MessageSourceKind::Bot);
});

test('DiscordMessageAdapter defaults to User source', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->sourceKind(rawMessage()))->toBe(MessageSourceKind::User);
});

test('DiscordMessageAdapter extracts reply target', function (): void {
    $adapter = new DiscordMessageAdapter;

    $reply = $adapter->extractReply(rawMessage([
        'type' => 19,
        'message_reference' => ['message_id' => 'abc-123'],
    ]));

    expect($reply?->replyToProviderMessageId)->toBe('abc-123');
});

test('DiscordMessageAdapter returns null reply when no reference', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->extractReply(rawMessage()))->toBeNull();
});

test('DiscordMessageAdapter counts mentioned roles', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->mentionRoleCount(rawMessage(['mention_roles' => ['r1', 'r2', 'r3']])))->toBe(3)
        ->and($adapter->mentionRoleCount(rawMessage()))->toBe(0);
});

test('DiscordMessageAdapter reads edited_timestamp', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->editedAt(rawMessage(['edited_timestamp' => '2024-01-01T00:00:00Z'])))
        ->toBe('2024-01-01T00:00:00Z')
        ->and($adapter->editedAt(rawMessage()))->toBeNull();
});

test('DiscordMessageAdapter extracts membership events for UserJoin', function (): void {
    $adapter = new DiscordMessageAdapter;

    $event = $adapter->extractMembershipEvent(rawMessage([
        'type' => 7,
        'timestamp' => '2024-01-01T00:00:00Z',
    ]));

    expect($event?->kind)->toBe('user_join');
});

test('DiscordMessageAdapter extracts boost tier from membership event', function (): void {
    $adapter = new DiscordMessageAdapter;

    $event = $adapter->extractMembershipEvent(rawMessage([
        'type' => 10,
        'timestamp' => '2024-01-01T00:00:00Z',
    ]));

    expect($event?->kind)->toBe('boost_tier_2');
});

test('DiscordMessageAdapter returns null membership event for normal messages', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->extractMembershipEvent(rawMessage()))->toBeNull();
});

test('DiscordMessageAdapter extracts mentions with positional index', function (): void {
    $adapter = new DiscordMessageAdapter;

    $mentions = $adapter->extractMentions(rawMessage(['mentions' => [
        ['id' => '1', 'username' => 'alice'],
        ['id' => '2', 'username' => 'bob'],
    ]]));

    expect($mentions)->toHaveCount(2)
        ->and($mentions[0]->mentionedProviderAccountId)->toBe('1')
        ->and($mentions[0]->position)->toBe(0)
        ->and($mentions[1]->mentionedProviderAccountId)->toBe('2')
        ->and($mentions[1]->position)->toBe(1);
});

test('DiscordMessageType covers every Discord message type in the spec', function (): void {
    foreach (DiscordMessageType::cases() as $case) {
        expect($case->toCanonical())->toBeInstanceOf(MessageKind::class);
    }
});

test('DiscordMessageAdapter extracts a thread with archival metadata', function (): void {
    $adapter = new DiscordMessageAdapter;

    $thread = $adapter->extractThread(rawMessage([
        'thread' => [
            'id' => 'thread-1',
            'name' => 'Discussão',
            'thread_metadata' => [
                'archived' => true,
                'auto_archive_duration' => 10080,
            ],
        ],
    ]));

    expect($thread)->not->toBeNull()
        ->and($thread->providerThreadId)->toBe('thread-1')
        ->and($thread->name)->toBe('Discussão')
        ->and($thread->archived)->toBeTrue()
        ->and($thread->autoArchiveDuration)->toBe(10080);
});

test('DiscordMessageAdapter returns null thread when payload has none', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->extractThread(rawMessage()))->toBeNull();
});

test('DiscordMessageAdapter carries mention username into MentionData', function (): void {
    $adapter = new DiscordMessageAdapter;

    $mentions = $adapter->extractMentions(rawMessage(['mentions' => [
        ['id' => '1', 'username' => 'alice'],
    ]]));

    expect($mentions[0]->mentionedUsername)->toBe('alice');
});

test('DiscordMessageAdapter extracts attachments with full metadata', function (): void {
    $adapter = new DiscordMessageAdapter;

    $attachments = $adapter->extractAttachments(rawMessage(['attachments' => [[
        'id' => '1',
        'url' => 'https://cdn.discordapp.com/foo.png',
        'filename' => 'foo.png',
        'content_type' => 'image/png',
        'size' => 2048,
        'width' => 640,
        'height' => 480,
    ]]]));

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->url)->toBe('https://cdn.discordapp.com/foo.png')
        ->and($attachments[0]->filename)->toBe('foo.png')
        ->and($attachments[0]->contentType)->toBe('image/png')
        ->and($attachments[0]->size)->toBe(2048)
        ->and($attachments[0]->width)->toBe(640)
        ->and($attachments[0]->height)->toBe(480);
});

test('DiscordMessageAdapter extracts embeds and parses the source domain', function (): void {
    $adapter = new DiscordMessageAdapter;

    $embeds = $adapter->extractEmbeds(rawMessage(['embeds' => [[
        'url' => 'https://github.com/laravel/laravel',
        'title' => 'laravel/laravel',
        'description' => 'Laravel skeleton',
        'type' => 'link',
        'thumbnail' => ['url' => 'https://github.githubassets.com/thumb.png'],
    ]]]));

    expect($embeds)->toHaveCount(1)
        ->and($embeds[0]->sourceDomain)->toBe('github.com')
        ->and($embeds[0]->kind)->toBe('link')
        ->and($embeds[0]->thumbnailUrl)->toBe('https://github.githubassets.com/thumb.png');
});

test('DiscordMessageAdapter produces UserJoin MembershipEvent on type 7', function (): void {
    $adapter = new DiscordMessageAdapter;

    $event = $adapter->extractMembershipEvent(rawMessage([
        'type' => 7,
        'timestamp' => '2024-01-01T00:00:00+00:00',
        'id' => 'msg-1',
    ]));

    expect($event)->not->toBeNull()
        ->and($event->kind)->toBe('user_join')
        ->and($event->occurredAt)->toBe('2024-01-01T00:00:00+00:00')
        ->and($event->metadata['provider_message_id'])->toBe('msg-1');
});

test('DiscordMessageAdapter maps boost tiers to canonical kind strings', function (int $discordType, string $canonical): void {
    $adapter = new DiscordMessageAdapter;

    $event = $adapter->extractMembershipEvent(rawMessage([
        'type' => $discordType,
        'timestamp' => '2024-01-01T00:00:00+00:00',
    ]));

    expect($event?->kind)->toBe($canonical);
})->with([
    [8, 'boost'],
    [9, 'boost_tier_1'],
    [10, 'boost_tier_2'],
    [11, 'boost_tier_3'],
]);

test('DiscordMessageAdapter returns null MembershipEvent for non-membership types', function (): void {
    $adapter = new DiscordMessageAdapter;

    expect($adapter->extractMembershipEvent(rawMessage(['type' => 0])))->toBeNull()
        ->and($adapter->extractMembershipEvent(rawMessage(['type' => 19])))->toBeNull();
});
