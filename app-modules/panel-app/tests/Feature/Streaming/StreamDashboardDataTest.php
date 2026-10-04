<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamDashboardPage;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\SubTier;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;

use function Pest\Livewire\livewire;

const DASHBOARD_ALERT_SCOPES = ['user:read:email', 'moderator:read:followers', 'channel:read:subscriptions', 'bits:read'];

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    config()->set('services.twitch.scopes.app', 'user:read:email');
    config()->set('services.twitch.bot.user_id');

    $this->user = User::factory()->streamer()->create();
    $this->actingAs($this->user);
});

/**
 * @param  array<int, string>  $grantedScopes
 */
function dashboardTwitchSource(User $user, array $grantedScopes = DASHBOARD_ALERT_SCOPES, ?ChatReader $chatReader = null): StreamerSource
{
    $identity = ExternalIdentity::factory()->create([
        'model_id' => $user->getKey(),
        'provider' => IdentityProvider::Twitch,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => 'canal_do_streamer', 'granted_scopes' => $grantedScopes],
    ]);

    $streamer = resolve(EnsureStreamer::class)->handle($user);
    $source = StreamerSource::query()->whereBelongsTo($streamer)->where('external_identity_id', $identity->getKey())->sole();
    $source->update(['chat_reader' => $chatReader]);

    return $source;
}

function enableBotAccount(): void
{
    config()->set('services.twitch.bot.user_id', '555000');
    config()->set('services.twitch.bot.refresh_token', 'refresh-token');
}

/**
 * @param  array<int, array{type: StreamEventType, value: string}>  $stats
 * @return array<string, string>
 */
function statValues(array $stats): array
{
    return array_column(array_map(fn (array $stat): array => ['type' => $stat['type']->value, 'value' => $stat['value']], $stats), 'value', 'type');
}

test('o painel mostra os números dos últimos 30 dias', function (): void {
    $source = dashboardTwitchSource($this->user);
    StreamEvent::factory()->forSource($source)->count(3)->create(['occurred_at' => now()->subDays(2)]);
    StreamEvent::factory()->forSource($source)->ofType(StreamEventType::Sub, new SubDetails(SubTier::Tier1))->count(2)->create();
    StreamEvent::factory()->forSource($source)->ofType(StreamEventType::GiftSub, new GiftSubDetails(SubTier::Tier1, total: 5))->create();
    StreamEvent::factory()->forSource($source)->ofType(StreamEventType::Cheer, new CheerDetails(bits: 250))->count(2)->create();
    StreamEvent::factory()->forSource($source)->count(10)->create(['occurred_at' => now()->subDays(40)]);

    livewire(StreamDashboardPage::class)
        ->assertViewHas('stats', fn (array $stats): bool => statValues($stats) === ['follow' => '3', 'sub' => '7', 'cheer' => '500', 'raid' => '0']);
});

test('a atividade recente mostra os 10 eventos mais novos com o resumo', function (): void {
    $source = dashboardTwitchSource($this->user);
    StreamEvent::factory()->forSource($source)->count(11)->sequence(fn ($sequence): array => ['occurred_at' => now()->subHours($sequence->index + 2)])->create();
    $newest = StreamEvent::factory()->forSource($source)->ofType(StreamEventType::Cheer, new CheerDetails(bits: 1_500))->create(['actor_display_name' => 'MariaCoda']);

    livewire(StreamDashboardPage::class)
        ->assertViewHas('recentActivity', fn (array $activity): bool => count($activity) === 10 && $activity[0]['id'] === $newest->id)
        ->assertSee('@MariaCoda')
        ->assertSee('1.500 bits');
});

test('o canal sem eventos mostra zeros e a atividade vazia', function (): void {
    livewire(StreamDashboardPage::class)
        ->assertViewHas('stats', fn (array $stats): bool => statValues($stats) === ['follow' => '0', 'sub' => '0', 'cheer' => '0', 'raid' => '0'])
        ->assertSee('Nenhum evento ainda');
});

test('o painel não mostra os eventos de outro streamer', function (): void {
    $otherSource = dashboardTwitchSource(User::factory()->streamer()->create());
    StreamEvent::factory()->forSource($otherSource)->count(4)->create();

    livewire(StreamDashboardPage::class)
        ->assertViewHas('stats', fn (array $stats): bool => statValues($stats)['follow'] === '0')
        ->assertViewHas('recentActivity', []);
});

test('conectar a Twitch leva as features escolhidas', function (array $data, array $features): void {
    livewire(StreamDashboardPage::class)
        ->callAction('connectTwitch', data: $data)
        ->assertRedirect(route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch', 'features' => $features]));
})->with([
    'só alertas' => [['chat' => false], ['alerts']],
    'chat pela própria conta' => [['chat' => true, 'chat_reader' => 'own_account'], ['alerts', 'chat_own_account']],
]);

test('sem a conta bot, a opção de chat pela he4rtdevs não é aceita', function (): void {
    livewire(StreamDashboardPage::class)
        ->callAction('connectTwitch', data: ['chat' => true, 'chat_reader' => 'he4rt_bot'])
        ->assertHasActionErrors(['chat_reader'])
        ->assertNoRedirect();
});

test('com a conta bot, o chat pode ser lido pela he4rtdevs', function (): void {
    enableBotAccount();

    livewire(StreamDashboardPage::class)
        ->callAction('connectTwitch', data: ['chat' => true, 'chat_reader' => 'he4rt_bot'])
        ->assertRedirect(route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch', 'features' => ['alerts', 'chat_he4rt_bot']]));
});

test('trocar o leitor com os escopos já concedidos não volta à Twitch', function (): void {
    enableBotAccount();
    $source = dashboardTwitchSource($this->user, [...DASHBOARD_ALERT_SCOPES, 'user:read:chat', 'user:bot', 'channel:bot'], ChatReader::OwnAccount);

    livewire(StreamDashboardPage::class)
        ->callAction(TestAction::make('chatReader')->arguments(['source' => $source->id]), data: ['chat_reader' => 'he4rt_bot'])
        ->assertHasNoActionErrors()
        ->assertNoRedirect()
        ->assertNotified('Leitor do chat atualizado');

    expect($source->refresh()->chat_reader)->toBe(ChatReader::He4rtBot);
});

test('escolher um leitor sem os escopos manda para a Twitch com a feature dele', function (): void {
    $source = dashboardTwitchSource($this->user);

    livewire(StreamDashboardPage::class)
        ->callAction(TestAction::make('chatReader')->arguments(['source' => $source->id]), data: ['chat_reader' => 'own_account'])
        ->assertRedirect(route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch', 'features' => ['alerts', 'chat_own_account']]));

    expect($source->refresh()->chat_reader)->toBe(ChatReader::OwnAccount);
});

test('tirar o chat da overlay limpa o leitor da fonte', function (): void {
    $source = dashboardTwitchSource($this->user, [...DASHBOARD_ALERT_SCOPES, 'user:read:chat', 'user:bot'], ChatReader::OwnAccount);

    livewire(StreamDashboardPage::class)
        ->callAction(TestAction::make('chatReader')->arguments(['source' => $source->id]), data: ['chat_reader' => 'none'])
        ->assertNoRedirect();

    expect($source->refresh()->chat_reader)->toBeNull();
});

test('o streamer liga e desliga a fonte pela lista', function (): void {
    $source = dashboardTwitchSource($this->user);

    livewire(StreamDashboardPage::class)
        ->assertSee('@canal_do_streamer')
        ->callAction(TestAction::make('toggleSource')->arguments(['source' => $source->id]))
        ->assertNotified('Fonte desligada');

    expect($source->refresh()->enabled)->toBeFalse();
});

test('a fonte de outro streamer não muda pelo painel', function (): void {
    $otherSource = dashboardTwitchSource(User::factory()->streamer()->create());
    dashboardTwitchSource($this->user);

    livewire(StreamDashboardPage::class)
        ->callAction(TestAction::make('toggleSource')->arguments(['source' => $otherSource->id]))
        ->callAction(TestAction::make('chatReader')->arguments(['source' => $otherSource->id]), data: ['chat_reader' => 'own_account']);

    $otherSource->refresh();

    expect($otherSource->enabled)->toBeTrue()
        ->and($otherSource->chat_reader)->toBeNull();
});

test('faltam permissões quando o leitor escolhido não tem os escopos', function (): void {
    dashboardTwitchSource($this->user, chatReader: ChatReader::OwnAccount);

    livewire(StreamDashboardPage::class)
        ->assertSee('Faltam permissões')
        ->assertActionHasUrl('reauthorizeTwitch', route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch', 'features' => ['alerts', 'chat_own_account']]));
});
