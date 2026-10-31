<?php

declare(strict_types=1);

use He4rt\IntegrationSlido\Broadcasting\SlidoQuestionChanged;
use He4rt\IntegrationSlido\Broadcasting\SlidoQuestionPatched;
use He4rt\IntegrationSlido\Broadcasting\SlidoQuestionsSynced;
use He4rt\IntegrationSlido\Data\SlidoEventLocation;
use He4rt\IntegrationSlido\Stream\QuestionFeed;
use He4rt\IntegrationSlido\Stream\SlidoStreamSession;
use He4rt\IntegrationSlido\Transport\Requests\AuthorizeParticipant;
use He4rt\IntegrationSlido\Transport\Requests\ExchangeAuthorizationCode;
use He4rt\IntegrationSlido\Transport\Requests\GetEvent;
use He4rt\IntegrationSlido\Transport\Requests\GetQuestions;
use He4rt\IntegrationSlido\Transport\Requests\RefreshParticipantToken;
use He4rt\IntegrationSlido\Transport\SlidoClient;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Event;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

const STREAM_EVENT_UUID = '96d0aba7-efed-4b29-a78a-609e6e4074c0';

function streamTokenResponse(string $suffix): MockResponse
{
    return MockResponse::make([
        'access_token' => 'access-'.$suffix,
        'refresh_token' => 'refresh-'.$suffix,
        'expires_in' => 600,
    ]);
}

function streamSession(Streamer $streamer, array &$logs = []): SlidoStreamSession
{
    return new SlidoStreamSession(
        client: resolve(SlidoClient::class),
        feed: new QuestionFeed,
        location: new SlidoEventLocation('jC9L7Lu93Vw3ssNhB92Pvb', STREAM_EVENT_UUID, 'eu1'),
        streamer: $streamer,
        log: function (string $line) use (&$logs): void {
            $logs[] = $line;
        },
    );
}

function fakeSlido(array $overrides = []): void
{
    MockClient::destroyGlobal();
    MockClient::global([
        AuthorizeParticipant::class => MockResponse::make(['redirectUri' => 'https://wall.sli.do/event/x/oauth-callback?code=c1']),
        ExchangeAuthorizationCode::class => streamTokenResponse('a'),
        RefreshParticipantToken::class => streamTokenResponse('b'),
        ...$overrides,
    ]);
}

function questionUpdateFrame(array $params): string
{
    return '42'.json_encode(['slido.event.question.update', ['target' => 'slido/events/'.STREAM_EVENT_UUID.'/', 'params' => $params]]);
}

beforeEach(function (): void {
    Event::fake([SlidoQuestionChanged::class, SlidoQuestionPatched::class, SlidoQuestionsSynced::class]);

    fakeSlido();
});

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('faz o handshake e assina o evento com o token de participante', function (): void {
    $session = streamSession(Streamer::factory()->create());

    expect($session->handle('0{"sid":"s","pingInterval":25000}'))->toBe(['40'])
        ->and($session->handle('40{"sid":"s2"}'))->toBe(['420["subscribe",{"accessToken":"access-a","targets":["slido/events/'.STREAM_EVENT_UUID.'/"]}]'])
        ->and($session->handle('2'))->toBe(['3']);
});

test('envia a pergunta atualizada para o canal da overlay do streamer', function (): void {
    $streamer = Streamer::factory()->create();
    $session = streamSession($streamer);

    $session->handle(questionUpdateFrame([
        'event_question_id' => 122_180_089,
        'type' => 'Question',
        'text' => 'eaeaeaeaeaeaea',
        'author' => ['name' => 'danielhe4rt'],
        'is_anonymous' => false,
        'is_public' => true,
        'is_highlighted' => true,
        'is_answered' => false,
        'date_created' => '2026-10-31T18:23:13.000Z',
        'pinned_replies' => [],
    ]));

    Event::assertDispatched(fn (SlidoQuestionChanged $event): bool => $event->streamer->is($streamer)
        && $event->question->id === 122_180_089
        && $event->question->isHighlighted
        && $event->broadcastOn()[0]->name === 'private-'.$streamer->overlayChannel());
});

test('envia só o que mudou quando o Slido manda um patch ou uma ação do moderador', function (string $raw, array $changes): void {
    $session = streamSession(Streamer::factory()->create());

    $session->handle($raw);

    Event::assertDispatched(fn (SlidoQuestionPatched $event): bool => $event->patch->toArray() === ['id' => 122_180_089, 'changes' => $changes]);
    Event::assertNotDispatched(SlidoQuestionChanged::class);
})->with([
    'voto' => ['42["slido.event.question.patch",{"params":{"score":2,"event_question_id":122180089}}]', ['score' => 2]],
    'destaque' => ['42["slido.event.question.admin.action",{"params":{"is_highlighted":true,"is_answered":false,"event_question_id":122180089}}]', ['isHighlighted' => true, 'isAnswered' => false]],
]);

test('ignora o comentário solto porque o update da pergunta pai traz a resposta', function (): void {
    $session = streamSession(Streamer::factory()->create());

    $session->handle('42'.json_encode(['newQuestion', ['params' => [
        'event_question_id' => 122_180_096,
        'type' => 'Comment',
        'parent' => ['id' => '122180088', 'type' => 'Question'],
        'text' => 'eaeaea',
    ]]]));

    Event::assertNotDispatched(SlidoQuestionChanged::class);
});

test('registra no log um evento do Slido que ainda não foi mapeado', function (): void {
    $logs = [];
    $session = streamSession(Streamer::factory()->create(), $logs);

    $session->handle('42["slido.event.question.delete",{"params":{"event_question_id":5}}]');
    $session->handle('42["slido.event.analytics.update",{"params":{}}]');

    expect($logs)->toHaveCount(1)
        ->and($logs[0])->toContain('slido.event.question.delete');
});

test('sincroniza as perguntas visíveis ao conectar', function (): void {
    fakeSlido([
        GetEvent::class => MockResponse::make(['sections' => [['uuid' => 'section-1', 'is_active' => true]]]),
        GetQuestions::class => MockResponse::make([
            ['event_question_id' => 1, 'type' => 'Question', 'text' => 'Primeira', 'is_public' => true],
            ['event_question_id' => 2, 'type' => 'Question', 'text' => 'Apagada', 'is_public' => true, 'date_deleted' => '2026-10-31T18:30:00.000Z'],
        ]),
    ]);

    streamSession(Streamer::factory()->create())->syncQuestions();

    Event::assertDispatched(fn (SlidoQuestionsSynced $event): bool => count($event->questions) === 1
        && $event->questions[0]->text === 'Primeira');
});

test('renova o token e assina de novo com o token novo', function (): void {
    $session = streamSession(Streamer::factory()->create());
    $session->handle('40{"sid":"s"}');

    expect($session->refreshToken())->toBe(['420["subscribe",{"accessToken":"access-b","targets":["slido/events/'.STREAM_EVENT_UUID.'/"]}]']);
});

test('emite um token novo quando o refresh é recusado', function (): void {
    fakeSlido([
        RefreshParticipantToken::class => MockResponse::make(['error' => 'invalid_grant'], 400),
    ]);

    $logs = [];
    $session = streamSession(Streamer::factory()->create(), $logs);
    $session->handle('40{"sid":"s"}');

    expect($session->refreshToken()[0])->toContain('"accessToken":"access-a"')
        ->and($logs[0])->toContain('Refresh recusado');
});
