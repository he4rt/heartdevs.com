<?php

declare(strict_types=1);

use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\OAuth\TwitchScopes;
use He4rt\IntegrationTwitch\OAuth\TwitchStreamerFeature;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;

beforeEach(function (): void {
    config()->set('services.twitch.client_id', 'fake-client-id');
    config()->set('services.twitch.client_secret', 'fake-secret');
    config()->set('services.twitch.scopes', [
        'app' => 'user:read:email',
        'admin' => 'user:read:email moderator:read:followers channel:bot',
    ]);
});

test('alertas e chat pela própria conta pedem os escopos de leitura do chat', function (): void {
    $scopes = TwitchScopes::requestedFor('app', User::factory()->streamer()->create(), [
        TwitchStreamerFeature::Alerts,
        TwitchStreamerFeature::ChatOwnAccount,
    ]);

    expect($scopes)->toBe(['user:read:email', 'moderator:read:followers', 'channel:read:subscriptions', 'bits:read', 'user:read:chat', 'user:bot']);
});

test('o chat pela conta bot pede só a permissão de bot no canal', function (): void {
    $scopes = TwitchScopes::requestedFor('app', User::factory()->streamer()->create(), [
        TwitchStreamerFeature::Alerts,
        TwitchStreamerFeature::ChatHe4rtBot,
    ]);

    expect($scopes)->toContain('channel:bot')
        ->not->toContain('user:read:chat');
});

test('reautorizar mantém os escopos já concedidos', function (): void {
    $streamer = User::factory()->streamer()->create();
    ExternalIdentity::factory()->create([
        'model_id' => $streamer->getKey(),
        'provider' => IdentityProvider::Twitch,
        'metadata' => ['granted_scopes' => ['user:read:email', 'user:read:chat', 'user:bot']],
    ]);

    expect(TwitchScopes::requestedFor('app', $streamer, [TwitchStreamerFeature::Alerts]))
        ->toContain('user:read:chat', 'user:bot', 'bits:read');
});

test('sem escolha, o streamer recebe os escopos de alerta', function (): void {
    expect(TwitchScopes::requestedFor('app', User::factory()->streamer()->create()))
        ->toBe(['user:read:email', 'moderator:read:followers', 'channel:read:subscriptions', 'bits:read']);
});

test('o membro sem a role recebe só os escopos do painel', function (): void {
    expect(TwitchScopes::requestedFor('app', User::factory()->create(), [TwitchStreamerFeature::ChatOwnAccount]))
        ->toBe(['user:read:email']);
});

test('a rota leva as features escolhidas até a Twitch e ignora as inventadas', function (): void {
    $this->actingAs(User::factory()->streamer()->create());

    $response = $this->get(route('oauth.redirect', [
        'panel' => 'app',
        'provider' => IdentityProvider::Twitch->value,
        'features' => ['alerts', 'chat_he4rt_bot', 'root'],
    ]));

    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
    $state = OAuthStateDTO::fromEncryptedString((string) $query['state']);

    expect(explode(' ', (string) $query['scope']))->toContain('channel:bot', 'bits:read')
        ->and($state->features)->toBe(['alerts', 'chat_he4rt_bot', 'root'])
        ->and(TwitchStreamerFeature::fromValues($state->features))->toBe([TwitchStreamerFeature::Alerts, TwitchStreamerFeature::ChatHe4rtBot]);
});

test('o state sem features continua válido', function (): void {
    $state = new OAuthStateDTO(intent: OAuthIntent::Link, provider: IdentityProvider::Twitch, panel: 'app');

    expect(OAuthStateDTO::fromEncryptedString((string) $state)->features)->toBeEmpty();
});

test('o leitor do chat sai dos escopos concedidos', function (array $grantedScopes, ?ChatReader $expected): void {
    expect(TwitchStreamerFeature::chatReaderFrom($grantedScopes))->toBe($expected);
})->with([
    'conta própria' => [['user:read:chat', 'user:bot'], ChatReader::OwnAccount],
    'conta bot' => [['channel:bot'], ChatReader::He4rtBot],
    'os dois, a própria conta vence' => [['channel:bot', 'user:read:chat', 'user:bot'], ChatReader::OwnAccount],
    'escopo pela metade' => [['user:read:chat'], null],
    'nenhum' => [[], null],
]);

test('a primeira conexão define o leitor do chat da fonte', function (): void {
    $user = User::factory()->streamer()->create();
    ExternalIdentity::factory()->create([
        'model_id' => $user->getKey(),
        'provider' => IdentityProvider::Twitch,
        'disconnected_at' => null,
        'metadata' => ['granted_scopes' => ['user:read:email', 'bits:read', 'user:read:chat', 'user:bot']],
    ]);

    resolve(EnsureStreamer::class)->handle($user);

    expect(StreamerSource::query()->sole()->chat_reader)->toBe(ChatReader::OwnAccount);
});
