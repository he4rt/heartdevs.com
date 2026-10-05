<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Actions\RepairStreamerTwitchSubscriptions;
use He4rt\IntegrationTwitch\Actions\SyncStreamerTwitchSubscriptions;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Exceptions\TwitchUnreachable;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Support\StreamerSubscriptionPlan;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

const REPAIR_CALLBACK = 'https://he4rt.example.com/api/webhooks/twitch/eventsub';

beforeEach(function (): void {
    config()->set('services.twitch.eventsub_callback', REPAIR_CALLBACK);
    config()->set('services.twitch.eventsub_secret', 'test-secret-at-least-ten-chars');

    $this->remote = new ArrayObject();
    $this->helixDown = false;
    $this->helix = new MockClient([
        ListSubscriptions::class => fn (): MockResponse => test()->helixDown
            ? MockResponse::make(['message' => 'Service Unavailable'], 503)
            : MockResponse::make(['data' => array_values(test()->remote->getArrayCopy()), 'pagination' => []]),
        CreateSubscription::class => function (PendingRequest $request): MockResponse {
            $body = $request->body()?->all() ?? [];

            return MockResponse::make(['data' => [[
                'id' => (string) Str::uuid(),
                'status' => 'webhook_callback_verification_pending',
                'type' => $body['type'],
                'condition' => $body['condition'],
                'cost' => 1,
            ]]], 202);
        },
        DeleteSubscription::class => MockResponse::make([], 204),
    ]);

    Cache::put('twitch_app_access_token', 'fake-token', 3_600);
    app()->instance(TwitchHelixConnector::class, new TwitchHelixConnector(
        tokenService: new TwitchAppTokenService(new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')),
        clientId: 'fake-client-id',
    )->withMockClient($this->helix));

    $this->source = StreamerSource::factory()->create();
});

/**
 * Creates every subscription the source needs, the same in the database and on Twitch.
 *
 * @return array<string, TwitchSubscription>
 */
function subscribeEverything(StreamerSource $source, TwitchSubscriptionStatus $status = TwitchSubscriptionStatus::Enabled): array
{
    $subscriptions = [];

    foreach (StreamerSubscriptionPlan::for($source) as [$type, $condition]) {
        $subscription = TwitchSubscription::query()->create([
            'subscription_id' => (string) Str::uuid(),
            'type' => $type->value,
            'status' => $status,
            'broadcaster_user_id' => $source->identity->external_account_id,
            'condition' => $condition,
            'transport' => 'webhook',
            'callback_url' => REPAIR_CALLBACK,
            'cost' => 1,
            'version' => $type->getVersion(),
            'streamer_source_id' => $source->getKey(),
        ]);

        test()->remote[$subscription->subscription_id] = onTwitch($subscription, $status->value);
        $subscriptions[$type->value] = $subscription;
    }

    return $subscriptions;
}

/**
 * @return array<string, mixed>
 */
function onTwitch(TwitchSubscription $subscription, string $status, string $callback = REPAIR_CALLBACK): array
{
    return [
        'id' => $subscription->subscription_id,
        'status' => $status,
        'type' => $subscription->type,
        'version' => $subscription->version,
        'condition' => $subscription->condition,
        'transport' => ['method' => 'webhook', 'callback' => $callback],
        'cost' => 1,
    ];
}

test('com tudo ativo na Twitch e no banco, o reparo não cria nem apaga nada', function (): void {
    subscribeEverything($this->source);

    resolve(RepairStreamerTwitchSubscriptions::class)->handle($this->source);

    $this->helix->assertSentCount(0, CreateSubscription::class);
    $this->helix->assertSentCount(0, DeleteSubscription::class);

    expect(TwitchSubscription::query()->count())->toBe(9);
});

test('a pendente que falhou na Twitch é apagada e recriada com o callback atual', function (): void {
    $follow = subscribeEverything($this->source)[TwitchEventSubType::ChannelFollow->value];
    $follow->update(['status' => TwitchSubscriptionStatus::VerificationPending]);
    $this->remote[$follow->subscription_id] = onTwitch($follow, TwitchSubscriptionStatus::VerificationFailed->value);

    resolve(RepairStreamerTwitchSubscriptions::class)->handle($this->source);

    $newFollow = TwitchSubscription::query()->where('type', TwitchEventSubType::ChannelFollow->value)->sole();

    expect($follow->fresh())->toBeNull()
        ->and($newFollow->callback_url)->toBe(REPAIR_CALLBACK)
        ->and($newFollow->streamer_source_id)->toBe($this->source->getKey());
    $this->helix->assertSentCount(1, DeleteSubscription::class);
    $this->helix->assertSentCount(1, CreateSubscription::class);
});

test('a inscrição que sumiu na Twitch sai do banco e é criada de novo', function (): void {
    $raid = subscribeEverything($this->source)[TwitchEventSubType::ChannelRaid->value];
    unset($this->remote[$raid->subscription_id]);

    resolve(RepairStreamerTwitchSubscriptions::class)->handle($this->source);

    expect($raid->fresh())->toBeNull()
        ->and(TwitchSubscription::query()->where('type', TwitchEventSubType::ChannelRaid->value)->count())->toBe(1);
    $this->helix->assertSentCount(0, DeleteSubscription::class);
    $this->helix->assertSentCount(1, CreateSubscription::class);
});

test('com a Helix fora do ar, nada muda no banco', function (): void {
    $follow = subscribeEverything($this->source, TwitchSubscriptionStatus::VerificationPending)[TwitchEventSubType::ChannelFollow->value];
    $this->helixDown = true;

    expect(fn () => resolve(RepairStreamerTwitchSubscriptions::class)->handle($this->source))
        ->toThrow(TwitchUnreachable::class);

    expect($follow->fresh()?->status)->toBe(TwitchSubscriptionStatus::VerificationPending)
        ->and(TwitchSubscription::query()->count())->toBe(9);
    $this->helix->assertSentCount(0, CreateSubscription::class);
});

test('a inscrição da Twitch com o nosso callback e sem linha no banco é adotada', function (): void {
    subscribeEverything($this->source);
    TwitchSubscription::query()->delete();

    resolve(RepairStreamerTwitchSubscriptions::class)->handle($this->source);

    expect(TwitchSubscription::query()->where('streamer_source_id', $this->source->getKey())->count())->toBe(9);
    $this->helix->assertSentCount(0, CreateSubscription::class);
});

test('a inscrição de outro callback não é adotada nem apagada', function (): void {
    $follow = subscribeEverything($this->source)[TwitchEventSubType::ChannelFollow->value];
    $this->remote->exchangeArray([$follow->subscription_id => onTwitch($follow, 'enabled', 'https://outro-ambiente.example.com/eventsub')]);
    TwitchSubscription::query()->delete();

    resolve(RepairStreamerTwitchSubscriptions::class)->handle($this->source);

    expect(TwitchSubscription::query()->where('subscription_id', $follow->subscription_id)->exists())->toBeFalse();
    $this->helix->assertSentCount(0, DeleteSubscription::class);
});

test('a sincronização recria a pendente há mais de 10 minutos e mantém a recente', function (): void {
    $subscriptions = subscribeEverything($this->source);
    $stuck = $subscriptions[TwitchEventSubType::ChannelFollow->value];
    $recent = $subscriptions[TwitchEventSubType::ChannelRaid->value];
    $stuck->forceFill(['status' => TwitchSubscriptionStatus::VerificationPending, 'created_at' => now()->subMinutes(15)])->save();
    $recent->forceFill(['status' => TwitchSubscriptionStatus::VerificationPending, 'created_at' => now()->subMinutes(2)])->save();

    resolve(SyncStreamerTwitchSubscriptions::class)->handle($this->source);

    expect($stuck->fresh())->toBeNull()
        ->and($recent->fresh())->not->toBeNull();
    $this->helix->assertSentCount(1, DeleteSubscription::class);
    $this->helix->assertSentCount(1, CreateSubscription::class);
});
