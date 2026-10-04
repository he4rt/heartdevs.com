<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityDisconnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamDashboardPage;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamOverlaysPage;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\PanelApp\Clusters\Streaming\StreamingPreviewData;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Event;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    config()->set('services.twitch.scopes.streamer', 'user:read:email moderator:read:followers channel:read:subscriptions bits:read');
});

function connectTwitch(User $user, ?array $grantedScopes): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_id' => $user->getKey(),
        'provider' => IdentityProvider::Twitch,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => array_filter([
            'username' => 'canal_do_streamer',
            'granted_scopes' => $grantedScopes,
        ]),
    ]);
}

test('o streamer acessa as páginas da Minha Live', function (string $page): void {
    $this->actingAs(User::factory()->streamer()->create());

    $this->get($page::getUrl())->assertOk();
})->with([
    'painel' => [StreamDashboardPage::class],
    'overlays' => [StreamOverlaysPage::class],
]);

test('um membro sem a role não acessa as páginas da Minha Live', function (string $page): void {
    $this->actingAs(User::factory()->create());

    $this->get($page::getUrl())->assertForbidden();
})->with([
    'painel' => [StreamDashboardPage::class],
    'overlays' => [StreamOverlaysPage::class],
]);

test('a Minha Live só aparece na navegação de quem pode usá-la', function (string $factoryState, bool $isListed): void {
    $user = $factoryState === 'streamer'
        ? User::factory()->streamer()->create()
        : User::factory()->create();

    $this->actingAs($user);

    expect(StreamingCluster::shouldRegisterNavigation())->toBe($isListed);
})->with([
    'streamer' => ['streamer', true],
    'membro' => ['membro', false],
]);

test('o painel mostra o canal conectado e pronto para alertas', function (): void {
    $streamer = User::factory()->streamer()->create();
    connectTwitch($streamer, ['user:read:email', 'moderator:read:followers', 'channel:read:subscriptions', 'bits:read']);

    $this->actingAs($streamer);

    livewire(StreamDashboardPage::class)
        ->assertSee('@canal_do_streamer')
        ->assertSee('Pronto para alertas')
        ->assertDontSee('Conecte sua Twitch')
        ->assertActionVisible('disconnectTwitch')
        ->assertActionHidden('reauthorizeTwitch')
        ->assertActionHidden('connectTwitch');
});

test('o painel pede reautorização quando faltam escopos', function (): void {
    $streamer = User::factory()->streamer()->create();
    connectTwitch($streamer, ['user:read:email']);

    $this->actingAs($streamer);

    livewire(StreamDashboardPage::class)
        ->assertSee('Faltam permissões')
        ->assertActionVisible('reauthorizeTwitch')
        ->assertActionHasUrl('reauthorizeTwitch', route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch']))
        ->assertActionVisible('disconnectTwitch');
});

test('o painel convida a conectar a Twitch quando não há conexão', function (): void {
    $this->actingAs(User::factory()->streamer()->create());

    livewire(StreamDashboardPage::class)
        ->assertSee('Conecte sua Twitch')
        ->assertDontSee('Pronto para alertas')
        ->assertActionVisible('connectTwitch')
        ->assertActionHasUrl('connectTwitch', route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch']))
        ->assertActionHidden('disconnectTwitch');
});

test('desconectar a Twitch pelo painel encerra a conexão do streamer', function (): void {
    $streamer = User::factory()->streamer()->create();
    $connection = connectTwitch($streamer, grantedScopes: null);

    $this->actingAs($streamer);

    Event::fake([ExternalIdentityDisconnected::class]);

    livewire(StreamDashboardPage::class)
        ->callAction('disconnectTwitch')
        ->assertNotified('Twitch desconectada')
        ->assertSee('Conecte sua Twitch')
        ->assertActionVisible('connectTwitch');

    expect($connection->fresh()?->disconnected_at)->not->toBeNull();

    Event::assertDispatched(fn (ExternalIdentityDisconnected $event): bool => $event->identity->is($connection));
});

test('desconectar sem conexão própria não mexe na conexão de outro usuário', function (): void {
    $otherConnection = connectTwitch(User::factory()->streamer()->create(), grantedScopes: null);

    $this->actingAs(User::factory()->streamer()->create());

    livewire(StreamDashboardPage::class)
        ->assertActionHidden('disconnectTwitch')
        ->call('mountAction', 'disconnectTwitch')
        ->call('callMountedAction');

    expect($otherConnection->fresh()?->disconnected_at)->toBeNull();
});

test('o painel ignora a conexão da Twitch de outro usuário', function (): void {
    connectTwitch(User::factory()->streamer()->create(), grantedScopes: null);

    $this->actingAs(User::factory()->streamer()->create());

    livewire(StreamDashboardPage::class)->assertSee('Conecte sua Twitch');
});

test('testar um alerta avisa que ele foi enviado', function (): void {
    $this->actingAs(User::factory()->streamer()->create());

    livewire(StreamDashboardPage::class)
        ->call('sendTestAlert', 'cheer')
        ->assertNotified('Alerta de Bits enviado');
});

test('o alerta de gift sub usa o valor do domínio', function (): void {
    $this->actingAs(User::factory()->streamer()->create());

    livewire(StreamDashboardPage::class)
        ->call('sendTestAlert', 'gift_sub')
        ->assertNotified('Alerta de Gift sub enviado');
});

test('um tipo de alerta desconhecido não dispara nada', function (string $type): void {
    $this->actingAs(User::factory()->streamer()->create());

    livewire(StreamDashboardPage::class)
        ->call('sendTestAlert', $type)
        ->assertNotNotified();
})->with([
    'tipo que não existe' => ['donation'],
    'valor antigo do gift sub' => ['gift-sub'],
]);

test('abrir a Minha Live pela primeira vez cria o streamer', function (string $page): void {
    $user = User::factory()->streamer()->create();
    $this->actingAs($user);

    $this->get($page::getUrl())->assertOk();
    $this->get($page::getUrl())->assertOk();

    expect(Streamer::query()->whereBelongsTo($user)->sole()->status)->toBe(StreamerStatus::Active);
})->with([
    'painel' => [StreamDashboardPage::class],
    'overlays' => [StreamOverlaysPage::class],
]);

test('o super-admin sem a role de streamer também ganha um streamer', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);

    $this->get(StreamDashboardPage::getUrl())->assertOk();

    expect(Streamer::query()->whereBelongsTo($admin)->exists())->toBeTrue();
});

test('as overlays mostram um link por cena com o token do streamer', function (): void {
    $streamer = User::factory()->streamer()->create();
    $token = StreamingPreviewData::overlayToken($streamer);

    $this->actingAs($streamer);

    livewire(StreamOverlaysPage::class)
        ->assertSet('overlayToken', $token)
        ->assertSee(array_map(fn (OverlayScene $scene): string => $scene->getLabel(), OverlayScene::cases()))
        ->assertViewHas('scenes', fn (array $scenes): bool => array_column($scenes, 'url') === array_map(
            fn (OverlayScene $scene): string => url(sprintf('/overlay/%s/%s', $token, $scene->value)),
            OverlayScene::cases(),
        ));
});

test('gerar novos links troca o token das overlays', function (): void {
    $streamer = User::factory()->streamer()->create();
    $oldToken = StreamingPreviewData::overlayToken($streamer);

    $this->actingAs($streamer);

    $component = livewire(StreamOverlaysPage::class)
        ->callAction('regenerateToken')
        ->assertNotified('Links das overlays atualizados');

    expect($component->get('overlayToken'))
        ->not->toBe($oldToken)
        ->toHaveLength(32);
});

test('o token das overlays não pode ser trocado pelo navegador', function (): void {
    $this->actingAs(User::factory()->streamer()->create());

    livewire(StreamOverlaysPage::class)->set('overlayToken', 'token-escolhido');
})->throws(CannotUpdateLockedPropertyException::class);
