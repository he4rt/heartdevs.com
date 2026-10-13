<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Data\SessionTotals;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Session\Queries\StreamSessionChat;
use He4rt\Streaming\Session\Queries\StreamSessionTotals;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;

beforeEach(function (): void {
    $this->source = StreamerSource::factory()->create();
    $this->session = StreamSession::factory()->forSource($this->source)->create([
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
    ]);
});

function sessionEvent(StreamSession $session, StreamEventType $type, mixed $details = null): StreamEvent
{
    return StreamEvent::factory()
        ->forSource($session->streamer->sources()->sole())
        ->ofType($type, $details)
        ->create(['stream_session_id' => $session->id, 'occurred_at' => $session->started_at->addMinutes(10)]);
}

function chatInSession(StreamerSource $source, ExternalIdentity $chatter, DateTimeInterface $sentAt, ?string $channelId = null): Message
{
    return Message::factory()->create([
        'platform' => IdentityProvider::Twitch,
        'channel_id' => $channelId ?? $source->identity->external_account_id,
        'external_identity_id' => $chatter->getKey(),
        'sent_at' => $sentAt,
    ]);
}

function totalsOf(StreamSession $session): SessionTotals
{
    $loaded = resolve(StreamSessionTotals::class)->apply(StreamSession::query())->findOrFail($session->id);

    return SessionTotals::of($loaded);
}

test('soma os eventos da sessão, contando os subs presenteados', function (): void {
    sessionEvent($this->session, StreamEventType::Follow);
    sessionEvent($this->session, StreamEventType::Follow);
    sessionEvent($this->session, StreamEventType::Sub);
    sessionEvent($this->session, StreamEventType::GiftSub, new GiftSubDetails(total: 5));
    sessionEvent($this->session, StreamEventType::Cheer, new CheerDetails(bits: 300));
    sessionEvent($this->session, StreamEventType::Cheer, new CheerDetails(bits: 200));
    sessionEvent($this->session, StreamEventType::Raid, new RaidDetails(viewers: 40));

    $totals = totalsOf($this->session);

    expect($totals)
        ->follows->toBe(2)
        ->subs->toBe(6)
        ->bits->toBe(500)
        ->raids->toBe(1)
        ->events->toBe(7)
        ->and($totals->hasNoData())->toBeFalse();
});

test('eventos de outra sessão ou fora de sessão não entram na soma', function (): void {
    $otherSession = StreamSession::factory()->forSource($this->source)->create(['started_at' => now()->subDay()]);
    sessionEvent($otherSession, StreamEventType::Follow);
    StreamEvent::factory()->forSource($this->source)->create(['stream_session_id' => null]);

    expect(totalsOf($this->session)->follows)->toBe(0);
});

test('conta as mensagens e os chatters do canal dentro do horário da sessão', function (): void {
    $ana = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch]);
    $bia = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch]);
    $started = $this->session->started_at;

    chatInSession($this->source, $ana, $started->addMinutes(5));
    chatInSession($this->source, $ana, $started->addMinutes(6));
    chatInSession($this->source, $bia, $started->addMinutes(7));
    chatInSession($this->source, $bia, $started->subMinute());
    chatInSession($this->source, $bia, $this->session->ended_at->addMinute());
    chatInSession($this->source, $bia, $started->addMinutes(8), channelId: 'outro-canal');

    $totals = totalsOf($this->session);

    expect($totals)
        ->messages->toBe(3)
        ->chatters->toBe(2);
});

test('uma sessão aberta conta as mensagens até agora', function (): void {
    $openSession = StreamSession::factory()->forSource($this->source)->create(['started_at' => now()->subMinutes(30)]);
    $chatter = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch]);

    chatInSession($this->source, $chatter, now()->subMinutes(10));

    expect(totalsOf($openSession)->messages)->toBe(1);
});

test('uma sessão sem evento e sem mensagem fica marcada como sem dados', function (): void {
    expect(totalsOf($this->session)->hasNoData())->toBeTrue();
});

test('lista quem mais falou na sessão, do maior para o menor', function (): void {
    $started = $this->session->started_at;
    $chatters = collect(['ana', 'bia', 'caio'])->mapWithKeys(fn (string $name): array => [
        $name => ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch, 'metadata' => ['username' => $name]]),
    ]);

    foreach (['ana' => 1, 'bia' => 3, 'caio' => 2] as $name => $messages) {
        foreach (range(1, $messages) as $minute) {
            chatInSession($this->source, $chatters[$name], $started->addMinutes($minute));
        }
    }

    chatInSession($this->source, $chatters['ana'], $started->subHour());

    expect(resolve(StreamSessionChat::class)->topChatters($this->session, 2))->toBe([
        ['username' => 'bia', 'messages' => 3],
        ['username' => 'caio', 'messages' => 2],
    ]);
});

test('mede o ritmo do chat nos últimos minutos da sessão aberta', function (): void {
    $openSession = StreamSession::factory()->forSource($this->source)->create(['started_at' => now()->subHour()]);
    $chatter = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch]);

    foreach (range(1, 6) as $minute) {
        chatInSession($this->source, $chatter, now()->subSeconds($minute * 30));
    }

    chatInSession($this->source, $chatter, now()->subMinutes(20));

    expect(resolve(StreamSessionChat::class)->messagesPerMinute($openSession, 5))->toBe(1.2);
});
