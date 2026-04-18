<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordMessageAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordModerationEventAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordReactionsAction;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordVoiceLogAction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageReactionDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordModerationEventDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordVoiceLogDTO;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

// ── Helpers ──────────────────────────────────────────────────────

function discordMessage(array $overrides = []): array
{
    $default = [
        'type' => 0,
        'content' => 'Olá, mundo!',
        'id' => '541468820355940381',
        'channel_id' => '541443469642563585',
        'timestamp' => '2019-02-03T04:03:46.777000+00:00',
        'edited_timestamp' => null,
        'author' => [
            'id' => '253642464697516033',
            'username' => 'letsch1',
            'global_name' => 'letsch',
            'avatar' => 'abc123',
            'bot' => false,
        ],
        'mentions' => [],
        'mention_roles' => [],
        'attachments' => [],
        'embeds' => [],
        'reactions' => [],
        'pinned' => false,
        'flags' => 0,
    ];

    return array_replace_recursive($default, $overrides);
}

function dynoVoiceLog(string $userId, string $channelId, string $action = 'joined'): array
{
    return discordMessage([
        'id' => (string) fake()->unique()->numberBetween(100000, 999999),
        'content' => '',
        'author' => ['id' => '155149108183695360', 'username' => 'Dyno', 'bot' => true],
        'embeds' => [[
            'description' => sprintf('**<@!%s> %s voice channel <#%s>**', $userId, $action, $channelId),
            'color' => $action === 'joined' ? 3066993 : 15158332,
        ]],
    ]);
}

function dynoModerationLog(string $username, string $discriminator, string $action): array
{
    return discordMessage([
        'id' => (string) fake()->unique()->numberBetween(100000, 999999),
        'content' => '',
        'author' => ['id' => '155149108183695360', 'username' => 'Dyno', 'bot' => true],
        'embeds' => [[
            'description' => sprintf('<:dynoSuccess:546395281093034015> ***%s#%s %s***', $username, $discriminator, $action),
        ]],
    ]);
}

function heartdevsModerationLog(string $subjectId, string $moderatorId, string $type, string $reason): array
{
    return discordMessage([
        'id' => (string) fake()->unique()->numberBetween(100000, 999999),
        'content' => '',
        'author' => ['id' => '123456789', 'username' => 'heartdevs.com', 'bot' => true],
        'embeds' => [[
            'title' => '🚔 » Punição',
            'fields' => [
                ['name' => '🧔 Usuário punido:', 'value' => sprintf('<@%s>', $subjectId)],
                ['name' => '🧑‍⚖️ Punido por:', 'value' => sprintf('<@%s>', $moderatorId)],
                ['name' => '📄 Tipo:', 'value' => $type],
                ['name' => '📢 Motivo:', 'value' => $reason],
            ],
        ]],
    ]);
}

function createTestIdentity(Tenant $tenant, string $discordId, string $username = 'testuser'): ExternalIdentity
{
    $user = User::query()->firstOrCreate(
        ['username' => $username],
        ['id' => Uuid::uuid4()->toString(), 'name' => $username, 'is_donator' => false],
    );
    $user->tenants()->syncWithoutDetaching([$tenant->getKey()]);

    return ExternalIdentity::query()->create([
        'provider' => IdentityProvider::Discord,
        'external_account_id' => $discordId,
        'tenant_id' => $tenant->getKey(),
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'type' => IdentityProvider::Discord->getType(),
        'credentials_type' => CredentialsType::OAuth2,
        'credentials' => ClientAccessManager::make(),
        'metadata' => ['user' => ['username' => $username, 'discriminator' => '0']],
    ]);
}

// ── DiscordMessageDTO Tests ──────────────────────────────────────

test('DiscordMessageDTO creates from discord dump format', function (): void {
    $raw = discordMessage();
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->discordMessageId)->toBe('541468820355940381')
        ->and($dto->channelId)->toBe('541443469642563585')
        ->and($dto->authorDiscordId)->toBe('253642464697516033')
        ->and($dto->authorUsername)->toBe('letsch1')
        ->and($dto->authorName)->toBe('letsch')
        ->and($dto->isBot)->toBeFalse()
        ->and($dto->content)->toBe('Olá, mundo!')
        ->and($dto->sentAt)->toBe('2019-02-03T04:03:46.777000+00:00');
});

test('DiscordMessageDTO falls back to username when global_name is null', function (): void {
    $raw = discordMessage(['author' => ['global_name' => null]]);
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->authorName)->toBe('letsch1');
});

test('DiscordMessageDTO detects bot messages', function (): void {
    $raw = discordMessage(['author' => ['bot' => true]]);
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->isBot)->toBeTrue();
});

test('DiscordMessageDTO preserves entire message in metadata', function (): void {
    $raw = discordMessage();
    $dto = DiscordMessageDTO::fromDump($raw);

    expect($dto->metadata)->toBe($raw);
});

test('DiscordMessageDTO::toDatabase maps fields and parses sent_at to Carbon', function (): void {
    $dto = DiscordMessageDTO::fromDump(discordMessage());
    $result = $dto->toDatabase(['tenant_id' => 1, 'obtained_experience' => 0]);

    expect($result['provider_message_id'])->toBe('541468820355940381')
        ->and($result['channel_id'])->toBe('541443469642563585')
        ->and($result['content'])->toBe('Olá, mundo!')
        ->and($result['sent_at'])->toBeInstanceOf(Carbon::class)
        ->and($result['metadata'])->toBeArray()
        ->and($result['tenant_id'])->toBe(1)
        ->and($result['obtained_experience'])->toBe(0);
});

// ── DiscordMessageReactionDTO Tests ──────────────────────────────

test('DiscordMessageReactionDTO returns empty array for message without reactions', function (): void {
    $raw = discordMessage(['reactions' => []]);
    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toBe([]);
});

test('DiscordMessageReactionDTO extracts unicode emoji with null emoji_id', function (): void {
    $raw = discordMessage(['reactions' => [[
        'emoji' => ['id' => null, 'name' => '👍'],
        'count' => 3,
        'count_details' => ['burst' => 0, 'normal' => 3],
    ]]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(1)
        ->and($reactions[0]->emojiId)->toBeNull()
        ->and($reactions[0]->emojiName)->toBe('👍')
        ->and($reactions[0]->count)->toBe(3);
});

test('DiscordMessageReactionDTO extracts custom discord emoji with snowflake id', function (): void {
    $raw = discordMessage(['reactions' => [[
        'emoji' => ['id' => '546395281093034015', 'name' => 'he4rt'],
        'count' => 5,
        'count_details' => ['burst' => 1, 'normal' => 4],
    ]]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(1)
        ->and($reactions[0]->emojiId)->toBe('546395281093034015')
        ->and($reactions[0]->emojiName)->toBe('he4rt')
        ->and($reactions[0]->countBurst)->toBe(1)
        ->and($reactions[0]->countNormal)->toBe(4);
});

test('DiscordMessageReactionDTO extracts multiple reactions from a single message', function (): void {
    $raw = discordMessage(['reactions' => [
        ['emoji' => ['id' => null, 'name' => '🔥'], 'count' => 2, 'count_details' => ['burst' => 0, 'normal' => 2]],
        ['emoji' => ['id' => '123', 'name' => 'custom'], 'count' => 1, 'count_details' => ['burst' => 0, 'normal' => 1]],
    ]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(2);
});

test('DiscordMessageReactionDTO skips malformed reactions without emoji name', function (): void {
    $raw = discordMessage(['reactions' => [
        ['emoji' => ['id' => null, 'name' => null], 'count' => 1, 'count_details' => ['burst' => 0, 'normal' => 1]],
        ['emoji' => ['id' => null, 'name' => '👍'], 'count' => 1, 'count_details' => ['burst' => 0, 'normal' => 1]],
    ]]);

    $reactions = DiscordMessageReactionDTO::fromDumpMessage($raw);

    expect($reactions)->toHaveCount(1)
        ->and($reactions[0]->emojiName)->toBe('👍');
});

test('DiscordMessageReactionDTO::toDatabase computes emoji_key with u: prefix for unicode', function (): void {
    $dto = new DiscordMessageReactionDTO(emojiId: null, emojiName: '🔥', count: 2, countBurst: 0, countNormal: 2);
    $result = $dto->toDatabase();

    expect($result['emoji_key'])->toBe('u:🔥')
        ->and($result['emoji_id'])->toBeNull()
        ->and($result['emoji_name'])->toBe('🔥');
});

test('DiscordMessageReactionDTO::toDatabase uses emoji_id as key for custom emojis', function (): void {
    $dto = new DiscordMessageReactionDTO(emojiId: '546395281093034015', emojiName: 'he4rt', count: 5, countBurst: 0, countNormal: 5);
    $result = $dto->toDatabase();

    expect($result['emoji_key'])->toBe('546395281093034015');
});

// ── DiscordVoiceLogDTO Tests ─────────────────────────────────────

test('DiscordVoiceLogDTO extracts voice join event from dyno embed', function (): void {
    $raw = dynoVoiceLog('529963308577456128', '452928009964355604', 'joined');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->userDiscordId)->toBe('529963308577456128')
        ->and($dto->voiceChannelId)->toBe('452928009964355604')
        ->and($dto->action)->toBe('joined');
});

test('DiscordVoiceLogDTO extracts voice leave event from dyno embed', function (): void {
    $raw = dynoVoiceLog('529963308577456128', '452928009964355604', 'left');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->action)->toBe('left');
});

test('DiscordVoiceLogDTO returns null for non-voice messages', function (): void {
    $raw = discordMessage();
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordVoiceLogDTO returns null for messages without embeds', function (): void {
    $raw = discordMessage(['embeds' => []]);
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordVoiceLogDTO::toDatabase maps joined to unmuted state', function (): void {
    $raw = dynoVoiceLog('123', '456', 'joined');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    $result = $dto->toDatabase();

    expect($result['state'])->toBe('unmuted')
        ->and($result['obtained_experience'])->toBe(0);
});

test('DiscordVoiceLogDTO::toDatabase maps left to disabled state', function (): void {
    $raw = dynoVoiceLog('123', '456', 'left');
    $dto = DiscordVoiceLogDTO::fromDump($raw);

    $result = $dto->toDatabase();

    expect($result['state'])->toBe('disabled');
});

// ── DiscordModerationEventDTO Tests ──────────────────────────────

test('DiscordModerationEventDTO extracts ban from dyno embed', function (): void {
    $raw = dynoModerationLog('someuser', '1234', 'was banned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->type)->toBe(ModerationType::Ban)
        ->and($dto->botDiscordId)->toBe('155149108183695360')
        ->and($dto->subjectUsername)->toBe('someuser')
        ->and($dto->subjectDiscriminator)->toBe('1234')
        ->and($dto->subjectDiscordId)->toBeNull();
});

test('DiscordModerationEventDTO extracts warn from dyno embed', function (): void {
    $raw = dynoModerationLog('anotheruser', '5678', 'has been warned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->type)->toBe(ModerationType::Warn);
});

test('DiscordModerationEventDTO extracts ban from heartdevs embed with reason', function (): void {
    $raw = heartdevsModerationLog('367487241171501076', '237242679283548160', 'Banimento', 'descumpriu regras');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->not->toBeNull()
        ->and($dto->type)->toBe(ModerationType::Ban)
        ->and($dto->botDiscordId)->toBe('123456789')
        ->and($dto->subjectDiscordId)->toBe('367487241171501076')
        ->and($dto->moderatorDiscordId)->toBe('237242679283548160')
        ->and($dto->reason)->toBe('descumpriu regras');
});

test('DiscordModerationEventDTO returns null for non-moderation messages', function (): void {
    $raw = discordMessage();
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordModerationEventDTO returns null for non-bot messages', function (): void {
    $raw = discordMessage(['author' => ['bot' => false]]);
    $dto = DiscordModerationEventDTO::fromDump($raw);

    expect($dto)->toBeNull();
});

test('DiscordModerationEventDTO::toDatabase exposes type and occurred_at without bot identifier', function (): void {
    $raw = heartdevsModerationLog('111', '222', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $result = $dto->toDatabase();

    expect($result['type'])->toBe(ModerationType::Ban)
        ->and($result['occurred_at'])->toBeInstanceOf(Carbon::class)
        ->and($result)->not->toHaveKey('source_bot');
});

// ── ImportDiscordMessageAction Tests ─────────────────────────────

test('it creates message linked to existing external identity', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $identity = createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->external_identity_id)->toBe($identity->id)
        ->and($message->content)->toBe('Olá, mundo!');
});

test('it creates user and external identity for unknown author', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $this->assertDatabaseHas('users', ['username' => 'letsch1']);
    $this->assertDatabaseHas('external_identities', [
        'provider' => 'discord',
        'external_account_id' => '253642464697516033',
        'tenant_id' => $tenant->getKey(),
    ]);
});

test('it upserts message when provider_message_id already exists', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $action = resolve(ImportDiscordMessageAction::class);
    $action->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());
    $action->handle(DiscordMessageDTO::fromDump(discordMessage(['content' => 'updated'])), $tenant->getKey());

    expect(Message::query()->count())->toBe(1)
        ->and(Message::query()->first()->content)->toBe('updated');
});

test('it sets obtained_experience to zero', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    expect($message->obtained_experience)->toBe(0);
});

test('it stores complete message metadata', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    expect($message->metadata)->toBeArray()
        ->and($message->metadata)->toHaveKeys(['type', 'content', 'author', 'channel_id']);
});

test('it parses sent_at from discord timestamp', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $action = resolve(ImportDiscordMessageAction::class);
    $message = $action->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    expect($message->sent_at)->toBeInstanceOf(Carbon::class)
        ->and($message->sent_at->year)->toBe(2019);
});

// ── ImportDiscordReactionsAction Tests ───────────────────────────

test('it persists reactions with reactable_type message and reactable_id', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $reactions = DiscordMessageReactionDTO::fromDumpMessage(discordMessage(['reactions' => [
        ['emoji' => ['id' => null, 'name' => '👍'], 'count' => 3, 'count_details' => ['burst' => 0, 'normal' => 3]],
        ['emoji' => ['id' => '546', 'name' => 'he4rt'], 'count' => 5, 'count_details' => ['burst' => 0, 'normal' => 5]],
    ]]));

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, $reactions, $tenant->getKey());

    expect(Reaction::query()->count())->toBe(2);

    $this->assertDatabaseHas('activity_reactions', [
        'reactable_type' => 'message',
        'reactable_id' => $message->id,
        'emoji_key' => 'u:👍',
        'count' => 3,
    ]);

    $this->assertDatabaseHas('activity_reactions', [
        'reactable_type' => 'message',
        'reactable_id' => $message->id,
        'emoji_key' => '546',
        'emoji_name' => 'he4rt',
    ]);
});

test('it is idempotent - re-running updates counts instead of duplicating', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);

    $reactions1 = [new DiscordMessageReactionDTO(emojiId: null, emojiName: '👍', count: 3, countBurst: 0, countNormal: 3)];
    $reactionsAction->handle($message, $reactions1, $tenant->getKey());

    $reactions2 = [new DiscordMessageReactionDTO(emojiId: null, emojiName: '👍', count: 7, countBurst: 1, countNormal: 6)];
    $reactionsAction->handle($message, $reactions2, $tenant->getKey());

    expect(Reaction::query()->count())->toBe(1)
        ->and(Reaction::query()->first()->count)->toBe(7);
});

test('it updates message reaction counters', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $reactions = [
        new DiscordMessageReactionDTO(emojiId: null, emojiName: '👍', count: 3, countBurst: 0, countNormal: 3),
        new DiscordMessageReactionDTO(emojiId: '546', emojiName: 'he4rt', count: 5, countBurst: 0, countNormal: 5),
    ];

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, $reactions, $tenant->getKey());

    $message->refresh();

    expect($message->reactions_count)->toBe(2)
        ->and($message->reactions_total)->toBe(8);
});

test('Message::reactions returns MorphMany relation', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $reactions = [new DiscordMessageReactionDTO(emojiId: null, emojiName: '🔥', count: 1, countBurst: 0, countNormal: 1)];

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, $reactions, $tenant->getKey());

    expect($message->reactions()->count())->toBe(1)
        ->and($message->reactions()->first()->emoji_name)->toBe('🔥');
});

test('it does nothing when reactions list is empty', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $reactionsAction = resolve(ImportDiscordReactionsAction::class);
    $reactionsAction->handle($message, [], $tenant->getKey());

    expect(Reaction::query()->count())->toBe(0);
});

// ── ImportDiscordVoiceLogAction Tests ────────────────────────────

test('it creates voice record from dyno join log', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '529963308577456128', 'voiceuser');

    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('529963308577456128', '452928009964355604', 'joined'));
    $channelMap = ['452928009964355604' => 'general-voice'];

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, $tenant->getKey(), $channelMap);

    expect($voice)->toBeInstanceOf(Voice::class)
        ->and($voice->state)->toBe('unmuted')
        ->and($voice->channel_name)->toBe('general-voice')
        ->and($voice->obtained_experience)->toBe(0);
});

test('it creates voice record from dyno leave log with disabled state', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '529963308577456128', 'voiceuser');

    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('529963308577456128', '452928009964355604', 'left'));

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, $tenant->getKey(), []);

    expect($voice->state)->toBe('disabled');
});

test('it skips when user has no external identity', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('999999999', '452928009964355604'));

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, $tenant->getKey(), []);

    expect($voice)->toBeNull();
});

test('it resolves channel name from channel map', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '529963308577456128', 'voiceuser');

    $dto = DiscordVoiceLogDTO::fromDump(dynoVoiceLog('529963308577456128', '999'));

    $action = resolve(ImportDiscordVoiceLogAction::class);
    $voice = $action->handle($dto, $tenant->getKey(), ['999' => 'music-channel']);

    expect($voice->channel_name)->toBe('music-channel');
});

// ── ImportDiscordModerationEventAction Tests ─────────────────────

test('it creates moderation event linked to subject identity (heartdevs)', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    $identity = createTestIdentity($tenant, '367487241171501076', 'punished_user');
    $bot = createTestIdentity($tenant, '123456789', 'heartdevs_bot');

    $raw = heartdevsModerationLog('367487241171501076', '237242679283548160', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto, $tenant->getKey());

    expect($event)->toBeInstanceOf(ModerationEvent::class)
        ->and($event->type)->toBe(ModerationType::Ban)
        ->and($event->external_identity_id)->toBe($identity->id)
        ->and($event->source_identity_id)->toBe($bot->id);
});

test('it creates moderation event with null subject when dyno username does not match', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $raw = dynoModerationLog('unknownuser', '9999', 'was banned');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto, $tenant->getKey());

    expect($event->external_identity_id)->toBeNull()
        ->and($event->type)->toBe(ModerationType::Ban);
});

test('it stores moderator identity from heartdevs embed', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '367487241171501076', 'subject');
    $moderator = createTestIdentity($tenant, '237242679283548160', 'moderator');

    $raw = heartdevsModerationLog('367487241171501076', '237242679283548160', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto, $tenant->getKey());

    expect($event->moderator_identity_id)->toBe($moderator->id);
});

test('it stores reason and occurred_at from embed', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $raw = heartdevsModerationLog('111', '222', 'Advertência', 'comportamento inadequado');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto, $tenant->getKey());

    expect($event->reason)->toBe('comportamento inadequado')
        ->and($event->occurred_at)->toBeInstanceOf(Carbon::class);
});

test('it links source_message_id when provided', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $raw = heartdevsModerationLog('111', '222', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $event = $action->handle($dto, $tenant->getKey(), $message->id);

    expect($event->source_message_id)->toBe($message->id);
});

test('it is idempotent when re-importing the same source message', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);
    createTestIdentity($tenant, '253642464697516033', 'letsch1');

    $msgAction = resolve(ImportDiscordMessageAction::class);
    $message = $msgAction->handle(DiscordMessageDTO::fromDump(discordMessage()), $tenant->getKey());

    $raw = heartdevsModerationLog('111', '222', 'Banimento', 'motivo');
    $dto = DiscordModerationEventDTO::fromDump($raw);

    $action = resolve(ImportDiscordModerationEventAction::class);
    $first = $action->handle($dto, $tenant->getKey(), $message->id);
    $second = $action->handle($dto, $tenant->getKey(), $message->id);

    expect($first->id)->toBe($second->id)
        ->and(ModerationEvent::query()->where('source_message_id', $message->id)->count())->toBe(1);
});

test('it disambiguates user name when author global_name collides', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'he4rt']);

    $action = resolve(ImportDiscordMessageAction::class);

    $action->handle(DiscordMessageDTO::fromDump(discordMessage([
        'id' => '900000000000000001',
        'author' => ['id' => '111', 'username' => 'user_a', 'global_name' => 'Joao', 'bot' => false],
    ])), $tenant->getKey());

    $action->handle(DiscordMessageDTO::fromDump(discordMessage([
        'id' => '900000000000000002',
        'author' => ['id' => '222', 'username' => 'user_b', 'global_name' => 'Joao', 'bot' => false],
    ])), $tenant->getKey());

    expect(User::query()->where('name', 'Joao')->count())->toBe(1)
        ->and(User::query()->where('username', 'user_a')->value('name'))->toBe('Joao')
        ->and(User::query()->where('username', 'user_b')->value('name'))->toBe('user_b');
});
