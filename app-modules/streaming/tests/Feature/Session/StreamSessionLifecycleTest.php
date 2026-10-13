<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Broadcasting\StreamSessionEnded;
use He4rt\Streaming\Broadcasting\StreamSessionStarted;
use He4rt\Streaming\DTOs\IncomingSessionChange;
use He4rt\Streaming\Session\Actions\EndStreamSession;
use He4rt\Streaming\Session\Actions\StartStreamSession;
use He4rt\Streaming\Session\Actions\UpdateStreamSession;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([StreamSessionStarted::class, StreamSessionEnded::class]);

    $this->source = StreamerSource::factory()->create();
});

function sessionChangeFor(StreamerSource $source, array $overrides = []): IncomingSessionChange
{
    return new IncomingSessionChange(...[
        'platform' => IdentityProvider::Twitch,
        'broadcasterId' => $source->identity->external_account_id,
        'at' => CarbonImmutable::now(),
        ...$overrides,
    ]);
}

test('a live começa e abre uma sessão com título e categoria', function (): void {
    $session = resolve(StartStreamSession::class)->handle(sessionChangeFor($this->source, [
        'platformStreamId' => 's-1',
        'title' => 'Live de PHP',
        'category' => 'Software and Game Development',
    ]));

    expect($session?->isOpen())->toBeTrue()
        ->and($session?->title)->toBe('Live de PHP')
        ->and($session?->streamer_id)->toBe($this->source->streamer_id);
    Event::assertDispatchedTimes(StreamSessionStarted::class, 1);
});

test('o mesmo online duas vezes mantém uma sessão só', function (): void {
    $start = resolve(StartStreamSession::class);

    $start->handle(sessionChangeFor($this->source, ['platformStreamId' => 's-1']));
    $start->handle(sessionChangeFor($this->source, ['platformStreamId' => 's-1']));

    expect(StreamSession::query()->count())->toBe(1);
    Event::assertDispatchedTimes(StreamSessionStarted::class, 1);
});

test('um online novo fecha a sessão que perdeu o offline', function (): void {
    $previous = StreamSession::factory()->forSource($this->source)->create(['platform_stream_id' => 's-1']);
    $at = CarbonImmutable::parse('2026-10-04 19:00:00');

    $current = resolve(StartStreamSession::class)->handle(sessionChangeFor($this->source, [
        'platformStreamId' => 's-2',
        'at' => $at,
    ]));

    expect($previous->refresh()->ended_at?->equalTo($at))->toBeTrue()
        ->and($current?->isOpen())->toBeTrue();
});

test('o título muda na sessão aberta', function (): void {
    $session = StreamSession::factory()->forSource($this->source)->create(['category' => 'Just Chatting']);

    resolve(UpdateStreamSession::class)->handle(sessionChangeFor($this->source, ['title' => 'Refatorando o ETL']));

    expect($session->refresh()->title)->toBe('Refatorando o ETL')
        ->and($session->category)->toBe('Just Chatting');
});

test('mudar o título fora do ar não mexe em sessão nenhuma', function (): void {
    $closed = StreamSession::factory()->forSource($this->source)->ended()->create(['title' => 'Live antiga']);

    $updated = resolve(UpdateStreamSession::class)->handle(sessionChangeFor($this->source, ['title' => 'Refatorando o ETL']));

    expect($updated)->toBeNull()
        ->and($closed->refresh()->title)->toBe('Live antiga');
});

test('a live termina e fecha a sessão aberta', function (): void {
    $session = StreamSession::factory()->forSource($this->source)->create();

    resolve(EndStreamSession::class)->handle(sessionChangeFor($this->source));

    expect($session->refresh()->isOpen())->toBeFalse();
    Event::assertDispatchedTimes(StreamSessionEnded::class, 1);
});

test('o offline sem sessão aberta não faz nada', function (): void {
    expect(resolve(EndStreamSession::class)->handle(sessionChangeFor($this->source)))->toBeNull();
    Event::assertNotDispatched(StreamSessionEnded::class);
});

test('o broadcast da sessão vai para o canal do streamer e respeita o toggle da fonte', function (): void {
    $session = StreamSession::factory()->forSource($this->source)->create();
    $disabled = StreamerSource::factory()->disabled()->create();

    $started = new StreamSessionStarted($this->source, $session);

    expect($started->broadcastOn()[0]->name)->toBe('private-'.$this->source->streamer->overlayChannel())
        ->and($started->broadcastAs())->toBe('session.started')
        ->and($started->broadcastWhen())->toBeTrue()
        ->and(new StreamSessionStarted($disabled, $session)->broadcastWhen())->toBeFalse();
});
