<?php

declare(strict_types=1);

use He4rt\BotDiscord\Moderation\DiscordModerationAdapter;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function makeUserWithDiscord(string $discordId = '999'): User
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    $user->providers()->create([
        'provider' => IdentityProvider::Discord->value,
        'external_account_id' => $discordId,
        'tenant_id' => $tenant->id,
        'type' => 'external',
        'credentials_type' => 'oauth2',
        'credentials' => ClientAccessManager::make(),
    ]);

    return $user;
}

function makeAction(User $user, ActionType $type, ?string $duration = null): ModerationAction
{
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);

    return ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => $type,
        'target_platforms' => [Platform::Discord->value],
        'duration' => $duration,
    ]);
}

beforeEach(function (): void {
    config()->set('he4rt.discord.guild_id', '123456789');
    config()->set('discord.token', 'bot-token');
});

test('mute sends PATCH with communication_disabled_until', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $user = makeUserWithDiscord('111');
    $action = makeAction($user, ActionType::Mute, '24h');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains((string) $req->url(), '/guilds/123456789/members/111')
        && isset($req->data()['communication_disabled_until']));
});

test('mute 7d sends correct timeout duration', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $user = makeUserWithDiscord('222');
    $action = makeAction($user, ActionType::Mute, '7d');

    DiscordModerationAdapter::make()->execute($action, $user);

    Http::assertSent(function ($req): bool {
        $until = $req->data()['communication_disabled_until'] ?? null;

        return $until !== null && str_contains($until, now()->addDays(7)->format('Y-m-d'));
    });
});

test('mute 28d sends timeout capped at 28 days', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $user = makeUserWithDiscord('333');
    $action = makeAction($user, ActionType::Mute, '28d');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    Http::assertSent(function ($req): bool {
        $until = $req->data()['communication_disabled_until'] ?? null;

        return $until !== null && str_contains($until, now()->addDays(28)->format('Y-m-d'));
    });
});

test('kick sends DELETE to members endpoint', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 204)]);

    $user = makeUserWithDiscord('444');
    $action = makeAction($user, ActionType::Kick);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($req) => $req->method() === 'DELETE'
        && str_contains((string) $req->url(), '/guilds/123456789/members/444'));
});

test('ban 24h sends PUT to bans endpoint with delete_message_seconds 86400', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $user = makeUserWithDiscord('555');
    $action = makeAction($user, ActionType::Ban, '24h');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains((string) $req->url(), '/guilds/123456789/bans/555')
        && ($req->data()['delete_message_seconds'] ?? null) === 86400);
});

test('ban 7d sends delete_message_seconds 604800', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $user = makeUserWithDiscord('666');
    $action = makeAction($user, ActionType::Ban, '7d');

    DiscordModerationAdapter::make()->execute($action, $user);

    Http::assertSent(fn ($req) => ($req->data()['delete_message_seconds'] ?? null) === 604800);
});

test('permanent ban sends delete_message_seconds 0', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $user = makeUserWithDiscord('777');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    DiscordModerationAdapter::make()->execute($action, $user);

    Http::assertSent(fn ($req) => ($req->data()['delete_message_seconds'] ?? -1) === 0);
});

test('warn sends dm and returns success without calling guild api', function (): void {
    Http::fake(['discord.com/*' => Http::response(['id' => 'dm-channel-1'], 200)]);

    $user = makeUserWithDiscord('888');
    $action = makeAction($user, ActionType::Warn);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue()
        ->and($result->platformResponse['action'])->toBe('warn');

    Http::assertSent(fn ($req) => str_contains((string) $req->url(), '/users/@me/channels'));

    $guildCalls = collect(Http::recorded())->filter(
        fn ($recorded) => str_contains((string) $recorded[0]->url(), '/guilds/')
    );
    expect($guildCalls)->toBeEmpty();
});

test('content_remove sends dm and returns success without calling guild api', function (): void {
    Http::fake(['discord.com/*' => Http::response(['id' => 'dm-channel-2'], 200)]);

    $user = makeUserWithDiscord('889');
    $action = makeAction($user, ActionType::ContentRemove);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn ($req) => str_contains((string) $req->url(), '/users/@me/channels'));

    $guildCalls = collect(Http::recorded())->filter(
        fn ($recorded) => str_contains((string) $recorded[0]->url(), '/guilds/')
    );
    expect($guildCalls)->toBeEmpty();
});

test('notify sends dm to user with discord identity', function (): void {
    Http::fake(['discord.com/*' => Http::response(['id' => 'dm-chan-notify'], 200)]);

    $user = makeUserWithDiscord('notify-111');

    DiscordModerationAdapter::make()->notify($user, 'You have been warned.');

    Http::assertSent(fn ($req) => str_contains((string) $req->url(), '/users/@me/channels')
        && ($req->data()['recipient_id'] ?? null) === 'notify-111');

    Http::assertSent(fn ($req) => str_contains((string) $req->url(), '/channels/dm-chan-notify/messages'));
});

test('notify does nothing when user has no discord identity', function (): void {
    Http::fake();

    $user = User::factory()->create();

    DiscordModerationAdapter::make()->notify($user, 'You have been warned.');

    Http::assertNothingSent();
});

test('resolve user finds user by discord external id', function (): void {
    $user = makeUserWithDiscord('discord-abc');

    $resolved = DiscordModerationAdapter::make()->resolveUser('discord-abc');

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($user->id);
});

test('resolve user returns null when discord id not registered', function (): void {
    $resolved = DiscordModerationAdapter::make()->resolveUser('unknown-id-xyz');

    expect($resolved)->toBeNull();
});

test('ingest maps raw discord payload to ModerationContentDTO', function (): void {
    $payload = [
        'message_id' => 'msg-123',
        'author_id' => 'user-456',
        'content' => 'some bad text',
        'attachments' => ['http://cdn.discord.com/img.png'],
        'channel_id' => 'chan-789',
        'guild_id' => 'guild-111',
        'username' => 'badguy',
        'tenant_id' => null,
    ];

    $dto = DiscordModerationAdapter::make()->ingest($payload);

    expect($dto)->toBeInstanceOf(ModerationContentDTO::class)
        ->and($dto->contentId)->toBe('msg-123')
        ->and($dto->textContent)->toBe('some bad text')
        ->and($dto->authorExternalId)->toBe('user-456')
        ->and($dto->sourcePlatform)->toBe(Platform::Discord)
        ->and($dto->metadata['channel_id'])->toBe('chan-789')
        ->and($dto->metadata['guild_id'])->toBe('guild-111');
});

test('unknown duration sends null communication_disabled_until (permanent timeout removed)', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $user = makeUserWithDiscord('999');
    $action = makeAction($user, ActionType::Mute, '999d');

    DiscordModerationAdapter::make()->execute($action, $user);

    Http::assertSent(fn ($req) => array_key_exists('communication_disabled_until', $req->data())
        && $req->data()['communication_disabled_until'] === null);
});

test('returns failure when api responds with 403 forbidden', function (): void {
    Http::fake(['discord.com/*' => Http::response(['message' => 'Missing Permissions'], 403)]);

    $user = makeUserWithDiscord('aaaaa');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeNull();
});

test('returns failure when api responds with 429 rate limit', function (): void {
    Http::fake(['discord.com/*' => Http::response(['message' => 'You are being rate limited.'], 429)]);

    $user = makeUserWithDiscord('bbbbb');
    $action = makeAction($user, ActionType::Mute, '24h');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse();
});

test('returns failure when user has no discord identity', function (): void {
    Http::fake();

    $user = User::factory()->create();
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('Discord identity not found');

    Http::assertNothingSent();
});

test('returns failure when guild id is not configured', function (): void {
    Http::fake();

    config()->set('he4rt.discord.guild_id', '');

    $user = makeUserWithDiscord('ccccc');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('not configured');
});

test('returns failure when bot token is not configured', function (): void {
    Http::fake();

    config()->set('discord.token');
    config()->set('he4rt.discord.token');

    $user = makeUserWithDiscord('ddddd');
    $action = makeAction($user, ActionType::Kick);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('not configured');
});
