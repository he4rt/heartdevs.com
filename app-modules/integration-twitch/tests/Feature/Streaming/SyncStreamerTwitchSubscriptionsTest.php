<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Actions\SyncStreamerTwitchSubscriptions;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Actions\DisableStreamer;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSource;
use He4rt\Streaming\Streamer\Events\StreamerSourceUpdated;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

beforeEach(function (): void {
    config()->set('services.twitch.eventsub_callback', 'https://example.com/api/webhooks/twitch/eventsub');
    config()->set('services.twitch.eventsub_secret', 'test-secret-at-least-ten-chars');
    config()->set('services.twitch.bot.user_id', '555000');

    $this->helix = new MockClient([
        CreateSubscription::class => function (PendingRequest $request): MockResponse {
            $body = $request->body()?->all() ?? [];
            $isMissingChatScope = str_starts_with((string) ($body['type'] ?? ''), 'channel.chat.') && test()->chatScopeMissing === true;

            return $isMissingChatScope
                ? MockResponse::make(['status' => 403, 'message' => 'subscription missing proper authorization'], 403)
                : MockResponse::make(['data' => [[
                    'id' => (string) Str::uuid(),
                    'status' => 'webhook_callback_verification_pending',
                    'type' => $body['type'],
                    'condition' => $body['condition'],
                    'cost' => 1,
                ]]], 202);
        },
        DeleteSubscription::class => MockResponse::make([], 204),
    ]);
    $this->chatScopeMissing = false;

    Cache::put('twitch_app_access_token', 'fake-token', 3_600);
    app()->instance(TwitchHelixConnector::class, new TwitchHelixConnector(
        tokenService: new TwitchAppTokenService(new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')),
        clientId: 'fake-client-id',
    )->withMockClient($this->helix));
});

/**
 * @return array<int, string>
 */
function ownedSubscriptionTypes(StreamerSource $source): array
{
    return TwitchSubscription::query()
        ->where('streamer_source_id', $source->getKey())
        ->orderBy('type')
        ->pluck('type')
        ->all();
}

test('a fonte com alertas ganha as 9 inscrições marcadas com ela', function (): void {
    $source = StreamerSource::factory()->create();
    $broadcasterId = $source->identity->external_account_id;

    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);

    $follow = TwitchSubscription::query()->where('type', TwitchEventSubType::ChannelFollow->value)->sole();

    expect(ownedSubscriptionTypes($source))->toHaveCount(9)
        ->not->toContain(TwitchEventSubType::ChannelChatMessage->value)
        ->and($follow->condition)->toEqual(['broadcaster_user_id' => $broadcasterId, 'moderator_user_id' => $broadcasterId]);
});

test('sincronizar de novo não cria nada', function (): void {
    $source = StreamerSource::factory()->create();
    $sync = resolve(SyncStreamerTwitchSubscriptions::class);

    $sync->handle($source);
    $sync->handle($source);

    $this->helix->assertSentCount(9, CreateSubscription::class);
});

test('o chat lido pela própria conta usa o broadcaster como leitor', function (): void {
    $source = StreamerSource::factory()->readingChat(ChatReader::OwnAccount)->create();
    $broadcasterId = $source->identity->external_account_id;

    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);

    $chat = TwitchSubscription::query()->where('type', TwitchEventSubType::ChannelChatMessage->value)->sole();

    expect(ownedSubscriptionTypes($source))->toHaveCount(13)
        ->and($chat->condition)->toEqual(['broadcaster_user_id' => $broadcasterId, 'user_id' => $broadcasterId]);
});

test('a fonte com leitor de chat assina a limpeza do chat com a mesma condição', function (): void {
    $source = StreamerSource::factory()->readingChat(ChatReader::OwnAccount)->create();
    $broadcasterId = $source->identity->external_account_id;

    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);

    $clearConditions = TwitchSubscription::query()
        ->whereIn('type', [TwitchEventSubType::ChannelChatClear->value, TwitchEventSubType::ChannelChatClearUserMessages->value])
        ->pluck('condition')
        ->all();

    expect($clearConditions)->toHaveCount(2)
        ->each->toEqual(['broadcaster_user_id' => $broadcasterId, 'user_id' => $broadcasterId]);
});

test('trocar o leitor para a conta bot refaz as inscrições de chat', function (): void {
    $source = StreamerSource::factory()->readingChat(ChatReader::OwnAccount)->create();
    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);
    $ownChatIds = TwitchSubscription::query()->where('type', 'like', 'channel.chat.%')->pluck('subscription_id')->all();

    resolve(UpdateStreamerSource::class)->handle($source, enabled: true, chatReader: ChatReader::He4rtBot);
    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);

    $chat = TwitchSubscription::query()->where('type', TwitchEventSubType::ChannelChatMessage->value)->sole();

    expect($chat->condition['user_id'])->toBe('555000')
        ->and(TwitchSubscription::query()->whereIn('subscription_id', $ownChatIds)->exists())->toBeFalse();
    $this->helix->assertSentCount(4, DeleteSubscription::class);
});

test('desativar o streamer remove as inscrições dele na Twitch e no banco', function (): void {
    $source = StreamerSource::factory()->create();
    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);

    resolve(DisableStreamer::class)->handle($source->streamer);

    expect(ownedSubscriptionTypes($source))->toBeEmpty();
    $this->helix->assertSentCount(9, DeleteSubscription::class);
});

test('o canal da comunidade continua inscrito quando o streamer desconecta', function (): void {
    $source = StreamerSource::factory()->create();
    $broadcasterId = $source->identity->external_account_id;
    $community = TwitchSubscription::query()->create([
        'subscription_id' => 'community-online',
        'type' => TwitchEventSubType::StreamOnline->value,
        'status' => TwitchSubscriptionStatus::Enabled,
        'broadcaster_user_id' => $broadcasterId,
        'condition' => ['broadcaster_user_id' => $broadcasterId],
        'transport' => 'webhook',
        'cost' => 0,
        'version' => '1',
    ]);

    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);
    $source->identity->update(['disconnected_at' => now()]);
    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);

    expect($community->fresh())->not->toBeNull()
        ->and(ownedSubscriptionTypes($source))->toBeEmpty();
    $this->helix->assertSentCount(8, CreateSubscription::class);
    $this->helix->assertNotSent(fn (Request $request): bool => $request instanceof DeleteSubscription && $request->query()->get('id') === 'community-online');
});

test('sem escopo de chat, os alertas são criados e o erro vai para o log', function (): void {
    Log::spy();
    $this->chatScopeMissing = true;
    $source = StreamerSource::factory()->readingChat(ChatReader::OwnAccount)->create();

    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);

    expect(ownedSubscriptionTypes($source))->toHaveCount(9);
    Log::shouldHaveReceived('warning')->with('Twitch EventSub subscription failed', Mockery::on(fn (array $context): bool => $context['status'] === 403))->times(4);
});

test('a mudança na fonte dispara a sincronização', function (): void {
    $source = StreamerSource::factory()->create();

    event(new StreamerSourceUpdated($source));

    expect(ownedSubscriptionTypes($source))->toHaveCount(9);
});

test('sem o segredo do EventSub a sincronização não roda', function (): void {
    config()->set('services.twitch.eventsub_secret');
    $source = StreamerSource::factory()->create();

    event(new StreamerSourceUpdated($source));

    $this->helix->assertNothingSent();
});

test('o comando cria só as inscrições que faltam numa fonte antiga', function (): void {
    $source = StreamerSource::factory()->readingChat(ChatReader::OwnAccount)->create();
    resolve(SyncStreamerTwitchSubscriptions::class)->handle($source);
    TwitchSubscription::query()->where('type', 'like', 'channel.chat.clear%')->delete();
    $oldIds = TwitchSubscription::query()->pluck('subscription_id')->all();

    $this->artisan('twitch:sync-streamer-subscriptions', ['source' => $source->getKey()])
        ->expectsOutputToContain('13 subscriptions')
        ->assertSuccessful();

    $firstSyncCreates = 13;
    $missingClearTypes = 2;

    expect(ownedSubscriptionTypes($source))->toHaveCount(13)
        ->and(TwitchSubscription::query()->whereIn('subscription_id', $oldIds)->count())->toBe(11);
    $this->helix->assertSentCount($firstSyncCreates + $missingClearTypes, CreateSubscription::class);
});

test('o comando sem argumento sincroniza todas as fontes da Twitch', function (): void {
    $sources = StreamerSource::factory()->count(2)->create();

    $this->artisan('twitch:sync-streamer-subscriptions')->assertSuccessful();

    expect($sources->map(fn (StreamerSource $source): int => count(ownedSubscriptionTypes($source)))->all())->toBe([9, 9]);
});

test('o comando sem o segredo do EventSub não sincroniza', function (): void {
    config()->set('services.twitch.eventsub_secret');
    StreamerSource::factory()->create();

    $this->artisan('twitch:sync-streamer-subscriptions')->assertFailed();

    $this->helix->assertNothingSent();
});

test('o comando com uma fonte que não existe falha', function (): void {
    $this->artisan('twitch:sync-streamer-subscriptions', ['source' => (string) Str::uuid()])
        ->expectsOutputToContain('No Twitch streamer source found.')
        ->assertFailed();
});
