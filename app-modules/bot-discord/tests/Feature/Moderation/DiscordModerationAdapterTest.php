<?php

declare(strict_types=1);

use He4rt\BotDiscord\Moderation\DiscordModerationAdapter;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Bans\CreateBan;
use He4rt\IntegrationDiscord\Transport\Requests\Channels\CreateDmChannel;
use He4rt\IntegrationDiscord\Transport\Requests\Members\GetMember;
use He4rt\IntegrationDiscord\Transport\Requests\Members\ModifyMember;
use He4rt\IntegrationDiscord\Transport\Requests\Members\RemoveMember;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\CreateMessage;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\DeleteMessage;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

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

function mockConnector(array $responses = []): MockClient
{
    $mockClient = new MockClient($responses);

    $connector = resolve(DiscordConnector::class);
    $connector->withMockClient($mockClient);

    return $mockClient;
}

beforeEach(function (): void {
    config()->set('he4rt.discord.guild_id', '123456789');
    config()->set('discord.token', 'bot-token');
});

test('mute sends PATCH with communication_disabled_until', function (): void {
    $mockClient = mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        ModifyMember::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('111');
    $action = makeAction($user, ActionType::Mute, '24h');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    $mockClient->assertSent(ModifyMember::class);
    $mockClient->assertSent(fn ($request) => $request instanceof ModifyMember
        && str_contains($request->resolveEndpoint(), '/guilds/123456789/members/111'));
});

test('mute 7d sends correct timeout duration', function (): void {
    $mockClient = mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        ModifyMember::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('222');
    $action = makeAction($user, ActionType::Mute, '7d');

    DiscordModerationAdapter::make()->execute($action, $user);

    $mockClient->assertSent(fn ($request) => $request instanceof ModifyMember
        && str_contains($request->body()->all()['communication_disabled_until'] ?? '', now()->addDays(7)->format('Y-m-d')));
});

test('mute 28d sends timeout capped at 28 days', function (): void {
    $mockClient = mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        ModifyMember::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('333');
    $action = makeAction($user, ActionType::Mute, '28d');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    $mockClient->assertSent(fn ($request) => $request instanceof ModifyMember
        && str_contains($request->body()->all()['communication_disabled_until'] ?? '', now()->addDays(28)->format('Y-m-d')));
});

test('kick sends DELETE to members endpoint', function (): void {
    $mockClient = mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        RemoveMember::class => MockResponse::make([], 204),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('444');
    $action = makeAction($user, ActionType::Kick);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    $mockClient->assertSent(fn ($request) => $request instanceof RemoveMember
        && str_contains($request->resolveEndpoint(), '/guilds/123456789/members/444'));
});

test('ban 24h sends PUT to bans endpoint with delete_message_seconds 86400', function (): void {
    $mockClient = mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        CreateBan::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('555');
    $action = makeAction($user, ActionType::Ban, '24h');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    $mockClient->assertSent(fn ($request) => $request instanceof CreateBan
        && str_contains($request->resolveEndpoint(), '/guilds/123456789/bans/555')
        && ($request->body()->all()['delete_message_seconds'] ?? null) === 86_400);
});

test('ban 7d sends delete_message_seconds 604800', function (): void {
    $mockClient = mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        CreateBan::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('666');
    $action = makeAction($user, ActionType::Ban, '7d');

    DiscordModerationAdapter::make()->execute($action, $user);

    $mockClient->assertSent(fn ($request) => $request instanceof CreateBan
        && ($request->body()->all()['delete_message_seconds'] ?? null) === 604_800);
});

test('permanent ban sends delete_message_seconds 0', function (): void {
    $mockClient = mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        CreateBan::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('777');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    DiscordModerationAdapter::make()->execute($action, $user);

    $mockClient->assertSent(fn ($request) => $request instanceof CreateBan
        && ($request->body()->all()['delete_message_seconds'] ?? -1) === 0);
});

test('warn sends dm and returns success without calling guild api', function (): void {
    $mockClient = mockConnector([
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-channel-1'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('888');
    $action = makeAction($user, ActionType::Warn);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue()
        ->and($result->platformResponse['action'])->toBe('warn');

    $mockClient->assertSent(CreateDmChannel::class);
    $mockClient->assertNotSent(ModifyMember::class);
    $mockClient->assertNotSent(CreateBan::class);
    $mockClient->assertNotSent(RemoveMember::class);
});

test('content_remove sends dm and returns success without calling guild api', function (): void {
    $mockClient = mockConnector([
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-channel-2'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('889');
    $action = makeAction($user, ActionType::ContentRemove);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    $mockClient->assertSent(CreateDmChannel::class);
    $mockClient->assertNotSent(ModifyMember::class);
    $mockClient->assertNotSent(CreateBan::class);
    $mockClient->assertNotSent(RemoveMember::class);
});

test('notify sends dm to user with discord identity', function (): void {
    $mockClient = mockConnector([
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan-notify'], 200),
        CreateMessage::class => MockResponse::make([], 200),
    ]);

    $user = makeUserWithDiscord('notify-111');

    DiscordModerationAdapter::make()->notify($user, 'You have been warned.');

    $mockClient->assertSent(fn ($request) => $request instanceof CreateDmChannel
        && $request->body()->all()['recipient_id'] === 'notify-111');

    $mockClient->assertSent(fn ($request) => $request instanceof CreateMessage
        && str_contains($request->resolveEndpoint(), '/channels/dm-chan-notify/messages'));
});

test('notify does nothing when user has no discord identity', function (): void {
    $mockClient = mockConnector([]);

    $user = User::factory()->create();

    DiscordModerationAdapter::make()->notify($user, 'You have been warned.');

    $mockClient->assertNothingSent();
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

test('returns failure when api responds with 403 forbidden', function (): void {
    mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        CreateBan::class => MockResponse::make(['message' => 'Missing Permissions'], 403),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan'], 200),
        CreateMessage::class => MockResponse::make([], 200),
    ]);

    $user = makeUserWithDiscord('aaaaa');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeNull();
});

test('returns failure when api responds with 429 rate limit', function (): void {
    mockConnector([
        GetMember::class => MockResponse::make(['roles' => []], 200),
        ModifyMember::class => MockResponse::make(['message' => 'You are being rate limited.'], 429),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan'], 200),
        CreateMessage::class => MockResponse::make([], 200),
    ]);

    $user = makeUserWithDiscord('bbbbb');
    $action = makeAction($user, ActionType::Mute, '24h');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse();
});

test('returns failure when user has no discord identity', function (): void {
    $mockClient = mockConnector([]);

    $user = User::factory()->create();
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('Discord identity not found');

    $mockClient->assertNothingSent();
});

test('returns failure when guild id is not configured', function (): void {
    $mockClient = mockConnector([]);

    config()->set('he4rt.discord.guild_id', '');

    $user = makeUserWithDiscord('ccccc');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('not configured');
});

test('returns failure when bot token is not configured', function (): void {
    config()->set('discord.token', '');
    config()->set('he4rt.discord.token', '');

    // Rebind with empty token so the connector itself is recreated
    app()->singleton(DiscordConnector::class, fn () => new DiscordConnector(botToken: ''));

    $mockClient = mockConnector([
        GetMember::class => MockResponse::make([], 500),
    ]);

    $user = makeUserWithDiscord('ddddd');
    $action = makeAction($user, ActionType::Kick);

    // Since guild_id is still set, the adapter will try to call the role resolver
    // which will fail. The adapter catches the Throwable and returns failure.
    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse();
});

// --- Protection hierarchy tests ---

test('ban is blocked when target is an admin, regardless of who acts', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['547549573959385098']);
    config()->set('he4rt.discord.moderation.mod_role_ids', []);

    mockConnector([
        GetMember::class => MockResponse::make(['roles' => ['547549573959385098']], 200),
    ]);

    $user = makeUserWithDiscord('eeeee');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('administrator');
});

test('kick is blocked when target is an admin', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['547549573959385098']);
    config()->set('he4rt.discord.moderation.mod_role_ids', []);

    mockConnector([
        GetMember::class => MockResponse::make(['roles' => ['547549573959385098']], 200),
    ]);

    $user = makeUserWithDiscord('fffff');
    $action = makeAction($user, ActionType::Kick);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('administrator');
});

test('mute is blocked when target is an admin', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['547549573959385098']);
    config()->set('he4rt.discord.moderation.mod_role_ids', []);

    mockConnector([
        GetMember::class => MockResponse::make(['roles' => ['547549573959385098']], 200),
    ]);

    $user = makeUserWithDiscord('ggggg');
    $action = makeAction($user, ActionType::Mute, '24h');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('administrator');
});

test('ban is blocked when a mod tries to ban another mod', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['admin-role']);
    config()->set('he4rt.discord.moderation.mod_role_ids', ['mod-role']);

    $actor = makeUserWithDiscord('actor-mod');
    $target = makeUserWithDiscord('target-mod');

    $case = ModerationCase::factory()->create(['author_id' => $target->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => ActionType::Ban,
        'target_platforms' => [Platform::Discord->value],
        'moderator_id' => $actor->id,
    ]);

    // Both target and actor have mod-role
    mockConnector([
        GetMember::class => MockResponse::make(['roles' => ['mod-role']], 200),
    ]);

    $result = DiscordModerationAdapter::make()->execute($action, $target);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('moderators');
});

test('admin can ban a moderator', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['admin-role']);
    config()->set('he4rt.discord.moderation.mod_role_ids', ['mod-role']);

    $actor = makeUserWithDiscord('actor-admin');
    $target = makeUserWithDiscord('target-mod2');

    $case = ModerationCase::factory()->create(['author_id' => $target->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => ActionType::Ban,
        'target_platforms' => [Platform::Discord->value],
        'moderator_id' => $actor->id,
    ]);

    $getMemberCalls = 0;
    mockConnector([
        GetMember::class => static function () use (&$getMemberCalls): MockResponse {
            $getMemberCalls++;

            // First call is for target (mod-role), second is for actor (admin-role)
            return $getMemberCalls === 1
                ? MockResponse::make(['roles' => ['mod-role']], 200)
                : MockResponse::make(['roles' => ['admin-role']], 200);
        },
        CreateBan::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $result = DiscordModerationAdapter::make()->execute($action, $target);

    expect($result->success)->toBeTrue();
});

test('automated action cannot ban a moderator', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['admin-role']);
    config()->set('he4rt.discord.moderation.mod_role_ids', ['mod-role']);

    $target = makeUserWithDiscord('target-mod3');

    $case = ModerationCase::factory()->create(['author_id' => $target->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => ActionType::Ban,
        'target_platforms' => [Platform::Discord->value],
        'moderator_id' => null,
        'automated' => true,
    ]);

    mockConnector([
        GetMember::class => MockResponse::make(['roles' => ['mod-role']], 200),
    ]);

    $result = DiscordModerationAdapter::make()->execute($action, $target);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('moderators');
});

test('ban proceeds normally when target has no protected role', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['admin-role']);
    config()->set('he4rt.discord.moderation.mod_role_ids', ['mod-role']);

    mockConnector([
        GetMember::class => MockResponse::make(['roles' => ['123456789']], 200),
        CreateBan::class => MockResponse::make([], 200),
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-chan'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('hhhhh');
    $action = makeAction($user, ActionType::Ban, 'permanent');

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();
});

test('warn is not blocked even when target is an admin', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['admin-role']);

    mockConnector([
        CreateDmChannel::class => MockResponse::make(['id' => 'dm-channel-warn'], 200),
        CreateMessage::class => MockResponse::make([], 200),
        DeleteMessage::class => MockResponse::make([], 204),
    ]);

    $user = makeUserWithDiscord('iiiii');
    $action = makeAction($user, ActionType::Warn);

    $result = DiscordModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();
});
