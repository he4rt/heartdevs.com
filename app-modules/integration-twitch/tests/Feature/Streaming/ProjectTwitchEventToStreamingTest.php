<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Events\TwitchEventReceived;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Transport\Requests\Chat\GetChannelChatBadges;
use He4rt\IntegrationTwitch\Transport\Requests\Chat\GetGlobalChatBadges;
use He4rt\IntegrationTwitch\Transport\Requests\Streams\GetStreams;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use He4rt\Streaming\Chat\Data\ChatBadge;
use He4rt\Streaming\Chat\Data\ChatFragment;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\SubTier;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->source = StreamerSource::factory()->readingChat()->create();
    $this->broadcasterId = $this->source->identity->external_account_id;
});

function bindStreamsHelix(MockClient $mock): void
{
    Cache::put('twitch_app_access_token', 'fake-token', 3_600);

    app()->instance(TwitchHelixConnector::class, new TwitchHelixConnector(
        tokenService: new TwitchAppTokenService(new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')),
        clientId: 'fake-client-id',
    )->withMockClient($mock));
}

/**
 * @param  array<string, mixed>  $event
 */
function twitchCliLog(TwitchEventSubType $type, array $event, ?string $messageId = null): TwitchEventLog
{
    return TwitchEventLog::query()->create([
        'event_type' => $type->value,
        'broadcaster_user_id' => $event['broadcaster_user_id'] ?? $event['to_broadcaster_user_id'] ?? null,
        'user_id' => $event['user_id'] ?? $event['chatter_user_id'] ?? null,
        'twitch_message_id' => $messageId ?? (string) Str::uuid(),
        'payload' => [
            'subscription' => ['type' => $type->value, 'version' => $type->getVersion()],
            'event' => $event,
        ],
    ]);
}

const SUBSCRIBER_BADGE_URL = 'https://static-cdn.jtvnw.net/badges/v1/canal-subscriber-12/2';

/**
 * @param  list<array{string, string}>  $versions  pairs of version id and image URL
 * @return array{data: list<array<string, mixed>>}
 */
function helixBadgeSet(string $setId, array $versions): array
{
    return ['data' => [[
        'set_id' => $setId,
        'versions' => array_map(fn (array $version): array => [
            'id' => $version[0],
            'image_url_1x' => $version[1].'-1x',
            'image_url_2x' => $version[1],
            'image_url_4x' => $version[1].'-4x',
        ], $versions),
    ]]];
}

function bindBadgesHelix(): MockClient
{
    $mock = new MockClient([
        GetGlobalChatBadges::class => MockResponse::make(helixBadgeSet('subscriber', [['12', 'https://static-cdn.jtvnw.net/badges/v1/global-subscriber/2']])),
        GetChannelChatBadges::class => MockResponse::make(helixBadgeSet('subscriber', [['12', SUBSCRIBER_BADGE_URL]])),
    ]);

    bindStreamsHelix($mock);

    return $mock;
}

/**
 * @param  list<array<string, string>>  $badges
 */
function twitchChatLog(string $broadcasterId, string $messageId, array $badges): TwitchEventLog
{
    return twitchCliLog(TwitchEventSubType::ChannelChatMessage, [
        'broadcaster_user_id' => $broadcasterId,
        'chatter_user_id' => '9911',
        'chatter_user_login' => 'mariacoda',
        'chatter_user_name' => 'MariaCoda',
        'message_id' => $messageId,
        'message' => ['text' => 'oi', 'fragments' => [['type' => 'text', 'text' => 'oi']]],
        'badges' => $badges,
        'message_type' => 'text',
    ]);
}

/**
 * @return array<string, string>
 */
function twitchCliViewer(): array
{
    return ['user_id' => '9911', 'user_login' => 'mariacoda', 'user_name' => 'MariaCoda'];
}

test('cada evento de alerta da Twitch vira um fato do streaming', function (TwitchEventSubType $type, array $event, StreamEventType $expectedType, mixed $expectedDetails): void {
    event(new TwitchEventReceived(twitchCliLog($type, ['broadcaster_user_id' => $this->broadcasterId, ...$event])));

    $recorded = StreamEvent::query()->sole();

    expect($recorded->type)->toBe($expectedType)
        ->and($recorded->details)->toEqual($expectedDetails)
        ->and($recorded->streamer_id)->toBe($this->source->streamer_id);
})->with([
    'channel.follow' => [TwitchEventSubType::ChannelFollow, [...twitchCliViewer(), 'followed_at' => '2026-10-04T21:00:00Z'], StreamEventType::Follow, null],
    'channel.subscription.message' => [TwitchEventSubType::ChannelSubscriptionMessage, [
        ...twitchCliViewer(), 'tier' => '2000', 'message' => ['text' => 'três meses!', 'emotes' => []], 'cumulative_months' => 3, 'streak_months' => 1, 'duration_months' => 1,
    ], StreamEventType::Sub, new SubDetails(SubTier::Tier2, months: 3, message: 'três meses!')],
    'channel.subscription.gift' => [TwitchEventSubType::ChannelSubscriptionGift, [
        ...twitchCliViewer(), 'total' => 5, 'tier' => '1000', 'cumulative_total' => 10, 'is_anonymous' => false,
    ], StreamEventType::GiftSub, new GiftSubDetails(SubTier::Tier1, total: 5)],
    'channel.cheer' => [TwitchEventSubType::ChannelCheer, [
        ...twitchCliViewer(), 'is_anonymous' => false, 'message' => 'Cheer500 boa live', 'bits' => 500,
    ], StreamEventType::Cheer, new CheerDetails(bits: 500, message: 'Cheer500 boa live')],
]);

test('o raid vira evento com quem trouxe a audiência', function (): void {
    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelRaid, [
        'from_broadcaster_user_id' => '1234',
        'from_broadcaster_user_login' => 'canaldoze',
        'from_broadcaster_user_name' => 'CanalDoZe',
        'to_broadcaster_user_id' => $this->broadcasterId,
        'viewers' => 42,
    ])));

    $raid = StreamEvent::query()->sole();

    expect($raid->type)->toBe(StreamEventType::Raid)
        ->and($raid->details)->toEqual(new RaidDetails(viewers: 42))
        ->and($raid->actor_login)->toBe('canaldoze');
});

test('o cheer anônimo vira evento sem ator', function (): void {
    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelCheer, [
        'broadcaster_user_id' => $this->broadcasterId,
        'is_anonymous' => true,
        'user_id' => null,
        'user_login' => null,
        'user_name' => null,
        'message' => '',
        'bits' => 100,
    ])));

    expect(StreamEvent::query()->sole()->actor())->toBeNull();
});

test('o gift de 5 subs grava um gift sub e descarta os subs de presente', function (): void {
    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelSubscriptionGift, [
        'broadcaster_user_id' => $this->broadcasterId, ...twitchCliViewer(), 'total' => 5, 'tier' => '1000', 'is_anonymous' => false,
    ])));

    foreach (range(1, 5) as $recipient) {
        event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelSubscribe, [
            'broadcaster_user_id' => $this->broadcasterId,
            'user_id' => (string) $recipient,
            'user_login' => 'presenteado'.$recipient,
            'user_name' => 'Presenteado'.$recipient,
            'tier' => '1000',
            'is_gift' => true,
        ])));
    }

    expect(StreamEvent::query()->where('type', StreamEventType::GiftSub)->count())->toBe(1)
        ->and(StreamEvent::query()->where('type', StreamEventType::Sub)->exists())->toBeFalse();
});

test('o stream.online abre a sessão com título e categoria da Helix', function (): void {
    bindStreamsHelix(new MockClient([
        GetStreams::class => MockResponse::make(['data' => [[
            'id' => '40078987165',
            'user_id' => $this->broadcasterId,
            'title' => 'Refatorando o ETL',
            'game_name' => 'Software and Game Development',
        ]]]),
    ]));

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::StreamOnline, [
        'id' => '40078987165',
        'broadcaster_user_id' => $this->broadcasterId,
        'type' => 'live',
        'started_at' => '2026-10-04T21:00:00Z',
    ])));

    $session = StreamSession::query()->sole();

    expect($session->isOpen())->toBeTrue()
        ->and($session->platform_stream_id)->toBe('40078987165')
        ->and($session->title)->toBe('Refatorando o ETL')
        ->and($session->category)->toBe('Software and Game Development');
});

test('a sessão abre mesmo quando a Helix falha', function (): void {
    bindStreamsHelix(new MockClient([
        GetStreams::class => MockResponse::make(['message' => 'Service Unavailable'], 503),
    ]));

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::StreamOnline, [
        'id' => '40078987165',
        'broadcaster_user_id' => $this->broadcasterId,
        'started_at' => '2026-10-04T21:00:00Z',
    ])));

    expect(StreamSession::query()->sole()->title)->toBeNull();
});

test('o stream.online de um canal sem streamer não consulta a Helix', function (): void {
    $mock = new MockClient([GetStreams::class => MockResponse::make(['data' => []])]);
    bindStreamsHelix($mock);

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::StreamOnline, [
        'id' => '40078987165',
        'broadcaster_user_id' => '227168488',
        'started_at' => '2026-10-04T21:00:00Z',
    ])));

    $mock->assertNothingSent();
    expect(StreamSession::query()->exists())->toBeFalse();
});

test('o channel.update e o stream.offline mexem na sessão aberta', function (): void {
    $session = StreamSession::factory()->forSource($this->source)->create();

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelUpdate, [
        'broadcaster_user_id' => $this->broadcasterId,
        'title' => 'Live de PHP',
        'category_name' => 'Just Chatting',
    ])));
    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::StreamOffline, [
        'broadcaster_user_id' => $this->broadcasterId,
    ])));

    $session->refresh();

    expect($session->title)->toBe('Live de PHP')
        ->and($session->category)->toBe('Just Chatting')
        ->and($session->isOpen())->toBeFalse();
});

test('a mensagem do chat vai para a atividade com badges e emotes', function (): void {
    bindBadgesHelix();

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelChatMessage, [
        'broadcaster_user_id' => $this->broadcasterId,
        'chatter_user_id' => '9911',
        'chatter_user_login' => 'mariacoda',
        'chatter_user_name' => 'MariaCoda',
        'message_id' => 'cc106a89-1814-919d-454c-f4f2f970aae7',
        'message' => [
            'text' => 'boa noite Kappa',
            'fragments' => [
                ['type' => 'text', 'text' => 'boa noite ', 'cheermote' => null, 'emote' => null, 'mention' => null],
                ['type' => 'emote', 'text' => 'Kappa', 'cheermote' => null, 'emote' => ['id' => '25', 'emote_set_id' => '0'], 'mention' => null],
            ],
        ],
        'color' => '#00FF7F',
        'badges' => [['set_id' => 'subscriber', 'id' => '12', 'info' => '16']],
        'message_type' => 'text',
    ])));

    $message = Message::query()->sole();

    expect($message->content)->toBe('boa noite Kappa')
        ->and($message->provider_message_id)->toBe('cc106a89-1814-919d-454c-f4f2f970aae7')
        ->and(ChatMessageMetadata::fromArray($message->metadata ?? []))->toEqual(new ChatMessageMetadata(
            displayName: 'MariaCoda',
            color: '#00FF7F',
            badges: [new ChatBadge('subscriber', '12', SUBSCRIBER_BADGE_URL)],
            fragments: [
                ChatFragment::text('boa noite '),
                ChatFragment::emote('Kappa', '25', 'https://static-cdn.jtvnw.net/emoticons/v2/25/default/dark/1.0'),
            ],
        ));
});

test('a imagem do badge sai do catálogo da Helix, que fica em cache', function (): void {
    $mock = bindBadgesHelix();

    event(new TwitchEventReceived(twitchChatLog($this->broadcasterId, 'msg-1', [['set_id' => 'subscriber', 'id' => '12', 'info' => '16']])));
    event(new TwitchEventReceived(twitchChatLog($this->broadcasterId, 'msg-2', [['set_id' => 'subscriber', 'id' => '12', 'info' => '16']])));

    $badgeUrls = Message::query()->get()->map(fn (Message $message): ?string => ChatMessageMetadata::fromArray($message->metadata ?? [])->badges[0]->url);

    expect($badgeUrls->all())->toBe([SUBSCRIBER_BADGE_URL, SUBSCRIBER_BADGE_URL]);
    $mock->assertSentCount(1, GetGlobalChatBadges::class);
    $mock->assertSentCount(1, GetChannelChatBadges::class);
});

test('a mensagem sem badge não consulta a Helix', function (): void {
    $mock = bindBadgesHelix();

    event(new TwitchEventReceived(twitchChatLog($this->broadcasterId, 'msg-1', [])));

    expect(Message::query()->count())->toBe(1);
    $mock->assertNothingSent();
});

test('a mensagem entra com o badge sem imagem quando a Helix falha', function (): void {
    bindStreamsHelix(new MockClient([
        GetGlobalChatBadges::class => MockResponse::make(['message' => 'Service Unavailable'], 503),
        GetChannelChatBadges::class => MockResponse::make(['message' => 'Service Unavailable'], 503),
    ]));

    event(new TwitchEventReceived(twitchChatLog($this->broadcasterId, 'msg-1', [['set_id' => 'vip', 'id' => '1', 'info' => '']])));

    expect(ChatMessageMetadata::fromArray(Message::query()->sole()->metadata ?? [])->badges)
        ->toEqual([new ChatBadge('vip', '1')]);
});

test('a mensagem apagada pela moderação é marcada', function (): void {
    $message = Message::factory()->create([
        'platform' => 'twitch',
        'channel_id' => $this->broadcasterId,
        'provider_message_id' => 'msg-1',
        'metadata' => new ChatMessageMetadata('MariaCoda')->toArray(),
    ]);

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelChatMessageDelete, [
        'broadcaster_user_id' => $this->broadcasterId,
        'target_user_id' => '9911',
        'target_user_login' => 'mariacoda',
        'target_user_name' => 'MariaCoda',
        'message_id' => 'msg-1',
    ])));

    expect(ChatMessageMetadata::fromArray($message->refresh()->metadata ?? [])->isDeleted())->toBeTrue();
});

test('o ban de um chatter marca só as mensagens dele no canal', function (): void {
    $spammer = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch, 'external_account_id' => '9911']);
    $spam = Message::factory()->count(2)->create([
        'platform' => 'twitch',
        'channel_id' => $this->broadcasterId,
        'external_identity_id' => $spammer->getKey(),
        'metadata' => new ChatMessageMetadata('Spammer')->toArray(),
    ]);
    $greeting = Message::factory()->create([
        'platform' => 'twitch',
        'channel_id' => $this->broadcasterId,
        'metadata' => new ChatMessageMetadata('MariaCoda')->toArray(),
    ]);

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelChatClearUserMessages, [
        'broadcaster_user_id' => $this->broadcasterId,
        'target_user_id' => '9911',
        'target_user_login' => 'spammer',
        'target_user_name' => 'Spammer',
    ])));

    $isDeleted = fn (Message $message): bool => ChatMessageMetadata::fromArray($message->refresh()->metadata ?? [])->isDeleted();

    expect($spam->every($isDeleted))->toBeTrue()
        ->and($isDeleted($greeting))->toBeFalse();
});

test('o /clear da Twitch limpa o chat da overlay', function (): void {
    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelChatClear, [
        'broadcaster_user_id' => $this->broadcasterId,
        'broadcaster_user_login' => 'danielhe4rt',
        'broadcaster_user_name' => 'danielhe4rt',
    ])));

    expect($this->source->streamer->refresh()->chat_cleared_at)->not->toBeNull();
});

test('o /clear num canal sem chat na overlay não muda nada', function (): void {
    $this->source->update(['chat_reader' => null]);

    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelChatClear, [
        'broadcaster_user_id' => $this->broadcasterId,
    ])));

    expect($this->source->streamer->refresh()->chat_cleared_at)->toBeNull();
});

test('um tipo fora do escopo fica só no lake', function (): void {
    event(new TwitchEventReceived(twitchCliLog(TwitchEventSubType::ChannelPollBegin, [
        'broadcaster_user_id' => $this->broadcasterId,
        'title' => 'Qual linguagem?',
    ])));

    expect(TwitchEventLog::query()->count())->toBe(1)
        ->and(StreamEvent::query()->exists())->toBeFalse()
        ->and(StreamSession::query()->exists())->toBeFalse();
});

test('reprocessar o mesmo log não duplica o evento', function (): void {
    $log = twitchCliLog(TwitchEventSubType::ChannelFollow, ['broadcaster_user_id' => $this->broadcasterId, ...twitchCliViewer()]);

    event(new TwitchEventReceived($log));
    event(new TwitchEventReceived($log));

    expect(StreamEvent::query()->count())->toBe(1);
});
