<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\OAuth\TwitchScopes;
use He4rt\IntegrationTwitch\OAuth\TwitchStreamerFeature;
use He4rt\IntegrationTwitch\Support\StreamerSubscriptionPlan;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\RefreshUserToken;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\ValidateToken;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamDashboardPage;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    config()->set('services.twitch.eventsub_callback', 'https://he4rt.example.com/api/webhooks/twitch/eventsub');
    config()->set('services.twitch.eventsub_secret', 'test-secret-at-least-ten-chars');

    $this->oauth = new MockClient([ValidateToken::class => fn (): MockResponse => test()->tokenRefused
        ? MockResponse::make(['status' => 401], 401)
        : MockResponse::make(['scopes' => TwitchScopes::requestedFor('app', test()->user, [TwitchStreamerFeature::Alerts])]),
        RefreshUserToken::class => MockResponse::make(['status' => 400, 'message' => 'Invalid refresh token'], 400),
    ]);
    $this->tokenRefused = false;
    app()->instance(TwitchOAuthConnector::class, new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')->withMockClient($this->oauth));

    $this->helixDown = false;
    $this->helix = new MockClient([
        ListSubscriptions::class => fn (): MockResponse => test()->helixDown
            ? MockResponse::make(['message' => 'Service Unavailable'], 503)
            : MockResponse::make(['data' => [], 'pagination' => []]),
        CreateSubscription::class => function (PendingRequest $request): MockResponse {
            $body = $request->body()?->all() ?? [];

            return MockResponse::make(['data' => [['id' => (string) Str::uuid(), 'status' => 'enabled', 'type' => $body['type'], 'condition' => $body['condition'], 'cost' => 1]]], 202);
        },
    ]);
    Cache::put('twitch_app_access_token', 'fake-token', 3_600);
    app()->instance(TwitchHelixConnector::class, new TwitchHelixConnector(
        tokenService: new TwitchAppTokenService(new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')),
        clientId: 'fake-client-id',
    )->withMockClient($this->helix));

    app()->instance(OverlayConnections::class, new class implements OverlayConnections
    {
        public function count(Streamer $streamer): int
        {
            return 1;
        }
    });

    $this->user = User::factory()->streamer()->create();
    $this->streamer = resolve(EnsureStreamer::class)->handle($this->user);
    $identity = ExternalIdentity::factory()->create([
        'model_id' => $this->user->getKey(),
        'provider' => IdentityProvider::Twitch,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => 'canal_do_streamer', 'granted_scopes' => ['moderator:read:followers', 'channel:read:subscriptions', 'bits:read']],
    ]);
    $this->source = StreamerSource::factory()->create(['streamer_id' => $this->streamer->id, 'external_identity_id' => $identity->id]);
    $this->actingAs($this->user);
});

function activeSubscriptionsFor(StreamerSource $source): void
{
    foreach (StreamerSubscriptionPlan::for($source) as [$type, $condition]) {
        TwitchSubscription::query()->create([
            'subscription_id' => (string) Str::uuid(),
            'type' => $type->value,
            'status' => TwitchSubscriptionStatus::Enabled,
            'broadcaster_user_id' => $source->identity->external_account_id,
            'condition' => $condition,
            'transport' => 'webhook',
            'cost' => 1,
            'version' => $type->getVersion(),
            'streamer_source_id' => $source->getKey(),
        ]);
    }
}

test('a saúde só é verificada depois que a página carrega', function (): void {
    livewire(StreamDashboardPage::class)
        ->assertSee('Verificando a integração');

    $this->oauth->assertNothingSent();
});

test('com tudo certo, o painel mostra as cinco verificações em ordem', function (): void {
    activeSubscriptionsFor($this->source);

    $page = livewire(StreamDashboardPage::class)
        ->call('loadHealth')
        ->assertSee('Tudo certo')
        ->assertSee('9 de 9 ativas')
        ->assertSee('1 overlay aberta')
        ->assertDontSee('Reparar inscrições');

    expect($page->html())->toMatch('/Conta da Twitch.*Endereço do webhook.*Inscrições.*Último evento.*Overlays conectadas/s');
});

test('reparar inscrições pelo painel cria as que faltam', function (): void {
    livewire(StreamDashboardPage::class)
        ->call('loadHealth')
        ->assertSee('1 item precisa de atenção')
        ->assertSee('0 de 9 ativas · faltam 9')
        ->callAction('repairSubscriptions')
        ->assertNotified('Inscrições reparadas')
        ->assertSee('9 de 9 ativas');

    $this->helix->assertSentCount(9, CreateSubscription::class);
});

test('com a Twitch fora do ar, o reparo avisa e não muda nada', function (): void {
    $this->helixDown = true;

    livewire(StreamDashboardPage::class)
        ->call('loadHealth')
        ->callAction('repairSubscriptions')
        ->assertNotified('A Twitch não respondeu');

    expect(TwitchSubscription::query()->count())->toBe(0);
    $this->helix->assertSentCount(0, CreateSubscription::class);
});

test('autorização recusada mostra o botão de reconectar', function (): void {
    activeSubscriptionsFor($this->source);
    $this->tokenRefused = true;

    livewire(StreamDashboardPage::class)
        ->call('loadHealth')
        ->assertSee('A Twitch recusou a autorização')
        ->assertSee('Reconectar Twitch');
});

test('um erro na integração põe um selo vermelho no Painel e na Minha Live', function (): void {
    config()->set('services.twitch.eventsub_callback', 'https://he4rtdevs.test/api/webhooks/twitch/eventsub');
    activeSubscriptionsFor($this->source);

    expect(StreamDashboardPage::getNavigationBadge())->toBe('1')
        ->and(StreamDashboardPage::getNavigationBadgeColor())->toBe('danger')
        ->and(StreamingCluster::getNavigationBadge())->toBe('1');
    $this->oauth->assertNothingSent();
});

test('sem erro na integração não há selo no menu', function (): void {
    activeSubscriptionsFor($this->source);

    expect(StreamDashboardPage::getNavigationBadge())->toBeNull();
});

test('sem Twitch conectada a seção de saúde não aparece', function (): void {
    $this->source->identity->update(['disconnected_at' => now()]);

    livewire(StreamDashboardPage::class)
        ->assertDontSee('Verificando a integração')
        ->assertSee('Conecte sua Twitch');
});
