<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\IntegrationSlido\Transport\Requests\AuthorizeParticipant;
use He4rt\IntegrationSlido\Transport\Requests\ExchangeAuthorizationCode;
use He4rt\IntegrationSlido\Transport\Requests\GetEvent;
use He4rt\IntegrationSlido\Transport\Requests\GetEventByHash;
use He4rt\IntegrationSlido\Transport\Requests\GetQuestions;
use He4rt\IntegrationSlido\Transport\Requests\LookupEvent;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Data\MutedChatter;
use He4rt\Streaming\Streamer\Data\MutedChatters;
use He4rt\Streaming\Streamer\Data\PubSettings;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

const OVERLAY_TOKEN = '3bfa1a2ce19eadd54f3242055ea3a1a9';

beforeEach(function (): void {
    $this->streamer = Streamer::factory()->withToken(OVERLAY_TOKEN)->create();
});

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function overlayChatMessage(StreamerSource $source, int $minutesAgo): Message
{
    return Message::factory()->create([
        'platform' => $source->identity->provider,
        'channel_id' => $source->identity->external_account_id,
        'provider_message_id' => (string) Str::uuid(),
        'sent_at' => now()->subMinutes($minutesAgo),
        'metadata' => new ChatMessageMetadata('Espectador'.$minutesAgo)->toArray(),
    ]);
}

test('cada cena abre com o canal privado e as configurações do streamer', function (OverlayScene $scene, string $component): void {
    $this->get(route('overlays.show', ['token' => OVERLAY_TOKEN, 'scene' => $scene]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component($component)
            ->where('scene', $scene->value)
            ->where('handle', $this->streamer->user->username)
            ->where('channel', $this->streamer->overlayChannel())
            ->where('authEndpoint', route('overlays.broadcasting.auth', ['token' => OVERLAY_TOKEN]))
            ->where('settings', $this->streamer->settings->forScene($scene)->toArray())
            ->where('sources', expected: null)
            ->where('recentChat', [])
            ->where('session', expected: null));
})->with([
    'coworking' => [OverlayScene::Coworking, 'Overlays/Coworking'],
    'a live vai começar' => [OverlayScene::StartingSoon, 'Overlays/StartingSoon'],
    'sala de voz' => [OverlayScene::Voice, 'Overlays/Voice'],
    'chat' => [OverlayScene::Chat, 'Overlays/Chat'],
    'he4rt pub' => [OverlayScene::Pub, 'Overlays/Pub'],
]);

test('a cena da He4rt Pub abre com a edição, as perguntas e o QR Code do Slido', function (): void {
    $this->streamer->update(['settings' => $this->streamer->settings->withScene(new PubSettings('jC9L7Lu93Vw3ssNhB92Pvb'))]);

    MockClient::global([
        LookupEvent::class => MockResponse::make(['clusterId' => 'eu1', 'eventId' => '96d0aba7-efed-4b29-a78a-609e6e4074c0']),
        GetEventByHash::class => MockResponse::make([
            'name' => 'He4rt Pub #12',
            'code' => '9410834',
            'is_active' => true,
            'url' => ['app' => 'https://app.sli.do/event/jC9L7Lu93Vw3ssNhB92Pvb'],
        ]),
        AuthorizeParticipant::class => MockResponse::make(['redirectUri' => 'https://wall.sli.do/event/x/oauth-callback?code=c1']),
        ExchangeAuthorizationCode::class => MockResponse::make(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 600]),
        GetEvent::class => MockResponse::make(['sections' => [['uuid' => 'section-1', 'is_active' => true]]]),
        GetQuestions::class => MockResponse::make([
            ['event_question_id' => 1, 'type' => 'Question', 'text' => 'Qual a próxima edição?', 'is_public' => true, 'is_highlighted' => true],
        ]),
    ]);

    $this->get(route('overlays.show', ['token' => OVERLAY_TOKEN, 'scene' => OverlayScene::Pub]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('Overlays/Pub')
            ->where('pub.event.name', 'He4rt Pub #12')
            ->where('pub.event.code', '9410834')
            ->where('pub.questions.0.text', 'Qual a próxima edição?')
            ->where('pub.questions.0.isHighlighted', expected: true)
            ->where('pub.qrSvg', fn (string $svg): bool => str_contains($svg, '<svg')));

});

test('a cena da He4rt Pub abre sem os dados do Slido quando ele está fora do ar', function (): void {
    $this->streamer->update(['settings' => $this->streamer->settings->withScene(new PubSettings('jC9L7Lu93Vw3ssNhB92Pvb'))]);

    MockClient::global([
        LookupEvent::class => MockResponse::make(['message' => 'Bad Gateway'], 502),
    ]);

    $this->get(route('overlays.show', ['token' => OVERLAY_TOKEN, 'scene' => OverlayScene::Pub]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('Overlays/Pub')
            ->where('pub', expected: null));

});

test('a barra da overlay mostra o usuário do streamer, não o canal privado', function (): void {
    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('handle', fn (string $handle): bool => $handle === $this->streamer->user->username
                && !str_contains($handle, 'overlay.')));
});

test('a overlay abre sem login, como uma fonte de navegador do OBS', function (): void {
    $this->assertGuest();

    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')
        ->assertOk()
        ->assertSee('id="app"', escape: false);
});

test('um token desconhecido responde 404', function (): void {
    $this->get('/overlay/'.Str::lower(Str::random(32)).'/coworking')->assertNotFound();
});

test('a overlay de um streamer desativado responde 404', function (): void {
    $this->streamer->update(['status' => StreamerStatus::Disabled]);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')->assertNotFound();
});

test('uma cena desconhecida responde 404', function (): void {
    $this->get('/overlay/'.OVERLAY_TOKEN.'/alertas')->assertNotFound();
});

test('um token fora do formato responde 404', function (string $token): void {
    $this->get('/overlay/'.$token.'/coworking')->assertNotFound();
})->with([
    'curto demais' => ['abc123'],
    'com maiúsculas' => [mb_strtoupper(OVERLAY_TOKEN)],
    'com símbolos' => [str_repeat('-', 32)],
]);

test('ao recarregar, a overlay traz as 30 últimas mensagens em ordem cronológica', function (): void {
    $source = StreamerSource::factory()->for($this->streamer)->readingChat()->create();
    $messages = collect(range(1, 40))->mapWithKeys(fn (int $minutesAgo): array => [$minutesAgo => overlayChatMessage($source, $minutesAgo)]);
    $deleted = $messages[5];
    $deleted->update(['metadata' => ChatMessageMetadata::fromArray($deleted->metadata ?? [])->withDeletedAt(now()->toImmutable())->toArray()]);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('recentChat', 30)
            ->where('recentChat.0.msgId', $messages[31]->provider_message_id)
            ->where('recentChat.0.username', 'Espectador31')
            ->where('recentChat.29.msgId', $messages[1]->provider_message_id)
            ->where('recentChat', fn (Collection $chat): bool => $chat->doesntContain('msgId', $deleted->provider_message_id)));
});

test('depois de limpar o chat, a overlay só traz as mensagens novas', function (): void {
    $source = StreamerSource::factory()->for($this->streamer)->readingChat()->create();
    overlayChatMessage($source, minutesAgo: 10);
    $this->streamer->update(['chat_cleared_at' => now()->subMinutes(5)]);
    $after = overlayChatMessage($source, minutesAgo: 1);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('recentChat', 1)
            ->where('recentChat.0.msgId', $after->provider_message_id)
            ->where('recentChat.0.chatterId', $after->provider->external_account_id));
});

test('mensagens ocultas e de chatters silenciados não voltam ao recarregar', function (): void {
    $source = StreamerSource::factory()->for($this->streamer)->readingChat()->create();
    $hidden = overlayChatMessage($source, minutesAgo: 3);
    $hidden->update(['metadata' => ChatMessageMetadata::fromArray($hidden->metadata ?? [])->withHiddenAt(now()->toImmutable())->toArray()]);

    $muted = overlayChatMessage($source, minutesAgo: 2);
    $shown = overlayChatMessage($source, minutesAgo: 1);
    $this->streamer->update(['settings' => $this->streamer->settings->withMutedChatters(new MutedChatters()->with(
        new MutedChatter($muted->provider->provider, $muted->provider->external_account_id ?? '', 'Spammer', now()->toImmutable()),
    ))]);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/chat')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('recentChat', 1)
            ->where('recentChat.0.msgId', $shown->provider_message_id));
});

test('alertas antigos não voltam ao recarregar a overlay', function (): void {
    $source = StreamerSource::factory()->for($this->streamer)->create();
    StreamEvent::factory()->forSource($source)->count(5)->create();

    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->missing('alerts')
            ->missing('events'));
});

test('o chat de uma fonte desligada não aparece na overlay', function (): void {
    $enabled = StreamerSource::factory()->for($this->streamer)->readingChat()->create();
    $disabled = StreamerSource::factory()->for($this->streamer)->readingChat()->disabled()->create();
    $shown = overlayChatMessage($enabled, minutesAgo: 2);
    overlayChatMessage($disabled, minutesAgo: 1);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('recentChat', 1)
            ->where('recentChat.0.msgId', $shown->provider_message_id));
});

test('a live em andamento vem nas props da overlay', function (): void {
    $source = StreamerSource::factory()->for($this->streamer)->create();
    StreamSession::factory()->forSource($source)->create(['title' => 'Refatorando o ETL', 'category' => 'Software and Game Development']);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/starting')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('session.title', 'Refatorando o ETL')
            ->where('session.category', 'Software and Game Development')
            ->has('session.startedAt'));
});

test('a cena com fonte escolhida só traz o chat e a live dessa fonte', function (): void {
    $pubChannel = StreamerSource::factory()->for($this->streamer)->readingChat()->create();
    $ownChannel = StreamerSource::factory()->for($this->streamer)->readingChat()->create();
    $pubMessage = overlayChatMessage($pubChannel, minutesAgo: 2);
    overlayChatMessage($ownChannel, minutesAgo: 1);
    StreamSession::factory()->forSource($ownChannel)->create(['title' => 'Live do canal pessoal']);
    $this->streamer->update(['settings' => $this->streamer->settings->withSceneSources(OverlayScene::Pub, [$pubChannel->id])]);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/pub')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('sources', [$pubChannel->id])
            ->has('recentChat', 1)
            ->where('recentChat.0.msgId', $pubMessage->provider_message_id)
            ->where('recentChat.0.sourceId', $pubChannel->id)
            ->where('session', expected: null));

    $this->get('/overlay/'.OVERLAY_TOKEN.'/chat')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('sources', expected: null)
            ->has('recentChat', 2)
            ->where('session.title', 'Live do canal pessoal')
            ->where('session.sourceId', $ownChannel->id));
});

test('a cena volta a usar todas as fontes quando a fonte escolhida some', function (): void {
    $source = StreamerSource::factory()->for($this->streamer)->readingChat()->create();
    $gone = StreamerSource::factory()->for($this->streamer)->create();
    $this->streamer->update(['settings' => $this->streamer->settings->withSceneSources(OverlayScene::Pub, [$gone->id])]);
    $gone->delete();
    overlayChatMessage($source, minutesAgo: 1);

    $this->get('/overlay/'.OVERLAY_TOKEN.'/pub')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('sources', expected: null)
            ->has('recentChat', 1));
});
