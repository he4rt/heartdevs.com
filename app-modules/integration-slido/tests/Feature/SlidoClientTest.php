<?php

declare(strict_types=1);

use He4rt\IntegrationSlido\Data\SlidoEventLocation;
use He4rt\IntegrationSlido\Data\SlidoParticipantToken;
use He4rt\IntegrationSlido\Exceptions\SlidoResponseInvalid;
use He4rt\IntegrationSlido\Transport\Requests\AuthorizeParticipant;
use He4rt\IntegrationSlido\Transport\Requests\ExchangeAuthorizationCode;
use He4rt\IntegrationSlido\Transport\Requests\GetEvent;
use He4rt\IntegrationSlido\Transport\Requests\GetEventByHash;
use He4rt\IntegrationSlido\Transport\Requests\GetQuestions;
use He4rt\IntegrationSlido\Transport\Requests\LookupEvent;
use He4rt\IntegrationSlido\Transport\Requests\RefreshParticipantToken;
use He4rt\IntegrationSlido\Transport\SlidoClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

const SLIDO_HASH = 'jC9L7Lu93Vw3ssNhB92Pvb';
const SLIDO_UUID = '96d0aba7-efed-4b29-a78a-609e6e4074c0';
const SLIDO_SECTION = 'e4ca2f4d-23a5-4922-8b97-dd889dc27d5d';

function slidoLocation(): SlidoEventLocation
{
    return new SlidoEventLocation(SLIDO_HASH, SLIDO_UUID, 'eu1');
}

/**
 * @return array<string, string|int>
 */
function slidoTokenPayload(string $suffix = 'a'): array
{
    return [
        'access_token' => 'access-'.$suffix,
        'refresh_token' => 'refresh-'.$suffix,
        'expires_in' => 600,
        'refresh_token_expires_in' => 432_000,
        'token_type' => 'Bearer',
    ];
}

function slidoQuestionPayload(array $overrides = []): array
{
    return array_replace([
        'event_question_id' => 122_180_089,
        'type' => 'Question',
        'text' => 'Como vocês organizam a He4rt Pub?',
        'author' => ['name' => 'danielhe4rt'],
        'is_anonymous' => false,
        'is_public' => true,
        'is_answered' => false,
        'is_highlighted' => false,
        'score' => 3,
        'date_created' => '2026-10-31T18:23:13.000Z',
        'date_deleted' => null,
        'pinned_replies' => [],
    ], $overrides);
}

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('resolve o hash do evento para o cluster e o uuid', function (): void {
    MockClient::global([
        LookupEvent::class => MockResponse::make(['clusterId' => 'eu1', 'eventId' => SLIDO_UUID]),
    ]);

    $location = resolve(SlidoClient::class)->locate(SLIDO_HASH);

    expect($location->cluster)->toBe('eu1')
        ->and($location->uuid)->toBe(SLIDO_UUID)
        ->and($location->streamTarget())->toBe('slido/events/'.SLIDO_UUID.'/');
});

test('lê nome, código e link do participante do evento público', function (): void {
    MockClient::global([
        GetEventByHash::class => MockResponse::make([
            'name' => 'He4rt Pub #12',
            'code' => 9_410_834,
            'is_active' => true,
            'url' => ['app' => 'https://app.sli.do/event/'.SLIDO_HASH],
        ]),
    ]);

    $event = resolve(SlidoClient::class)->event(slidoLocation());

    expect($event->toArray())->toBe([
        'hash' => SLIDO_HASH,
        'name' => 'He4rt Pub #12',
        'code' => '9410834',
        'isActive' => true,
        'participantUrl' => 'https://app.sli.do/event/'.SLIDO_HASH,
    ]);
});

test('emite token de participante com PKCE sem cookie', function (): void {
    $mockClient = MockClient::global([
        AuthorizeParticipant::class => MockResponse::make([
            'redirectUri' => 'https://wall.sli.do/event/'.SLIDO_HASH.'/oauth-callback?cluster_id=eu1&code=auth-code&state=he4rt',
        ]),
        ExchangeAuthorizationCode::class => MockResponse::make(slidoTokenPayload()),
    ]);

    $token = resolve(SlidoClient::class)->issueParticipantToken(slidoLocation());

    expect($token->accessToken)->toBe('access-a')
        ->and($token->refreshToken)->toBe('refresh-a')
        ->and($token->needsRefresh())->toBeFalse();

    $authorizeQuery = $mockClient->findResponseByRequest(AuthorizeParticipant::class)?->getPendingRequest()->query()->all() ?? [];
    $exchangeBody = $mockClient->findResponseByRequest(ExchangeAuthorizationCode::class)?->getPendingRequest()->body()?->all() ?? [];
    $expectedChallenge = mb_rtrim(strtr(base64_encode(hash('sha256', (string) $exchangeBody['code_verifier'], binary: true)), '+/', '-_'), '=');

    expect($authorizeQuery)->toMatchArray([
        'scope' => AuthorizeParticipant::SCOPE,
        'code_challenge_method' => 'S256',
        'code_challenge' => $expectedChallenge,
        'redirect_uri' => 'https://wall.sli.do/event/'.SLIDO_HASH.'/oauth-callback',
    ])->and($exchangeBody)->toMatchArray([
        'code' => 'auth-code',
        'grant_type' => 'authorization_code',
    ]);
});

test('falha com mensagem clara quando o slido não devolve o code', function (): void {
    MockClient::global([
        AuthorizeParticipant::class => MockResponse::make([
            'redirectUri' => 'https://wall.sli.do/event/'.SLIDO_HASH.'/oauth-callback?error=login_required',
        ]),
    ]);

    resolve(SlidoClient::class)->issueParticipantToken(slidoLocation());
})->throws(SlidoResponseInvalid::class, 'authorization redirect');

test('renova o token usando o refresh token que rotaciona', function (): void {
    MockClient::global([
        RefreshParticipantToken::class => MockResponse::make(slidoTokenPayload('b')),
    ]);

    $client = resolve(SlidoClient::class);
    $token = SlidoParticipantToken::fromPayload(slidoTokenPayload());

    $refreshed = $client->refreshParticipantToken($token);

    expect($refreshed->accessToken)->toBe('access-b')
        ->and($refreshed->refreshToken)->toBe('refresh-b');
});

test('lista só as perguntas visíveis da seção ativa, com as respostas fixadas', function (): void {
    $mockClient = MockClient::global([
        GetEvent::class => MockResponse::make([
            'sections' => [
                ['uuid' => 'old-section', 'is_active' => false],
                ['uuid' => SLIDO_SECTION, 'is_active' => true],
            ],
        ]),
        GetQuestions::class => MockResponse::make([
            slidoQuestionPayload([
                'event_question_id' => 1,
                'is_anonymous' => true,
                'author' => [],
                'is_highlighted' => true,
                'pinned_replies' => [[
                    'event_question_id' => 9,
                    'type' => 'Comment',
                    'text' => 'Toda sexta, às 20h',
                    'author' => ['name' => 'Moderator'],
                    'attrs' => ['is_admin' => true],
                ]],
            ]),
            slidoQuestionPayload(['event_question_id' => 2, 'date_deleted' => '2026-10-31T18:30:00.000Z']),
            slidoQuestionPayload(['event_question_id' => 3, 'is_public' => false]),
        ]),
    ]);

    $questions = resolve(SlidoClient::class)->questions(slidoLocation(), SlidoParticipantToken::fromPayload(slidoTokenPayload()));

    expect($questions)->toHaveCount(1)
        ->and($questions[0]->toArray())->toMatchArray([
            'id' => 1,
            'authorName' => null,
            'isHighlighted' => true,
            'replies' => [['id' => 9, 'text' => 'Toda sexta, às 20h', 'authorName' => 'Moderator', 'isFromModerator' => true]],
        ]);

    $mockClient->assertSent(fn (GetQuestions $request, mixed $response): bool => $response->getPendingRequest()->query()->get('eventSectionId') === SLIDO_SECTION
        && $response->getPendingRequest()->headers()->get('Authorization') === 'Bearer access-a');
});
