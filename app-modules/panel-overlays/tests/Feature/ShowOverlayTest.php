<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

const OVERLAY_TOKEN = '3bfa1a2ce19eadd54f3242055ea3a1a9';

beforeEach(function (): void {
    $this->streamer = Streamer::factory()->withToken(OVERLAY_TOKEN)->create();
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
            ->where('channel', $this->streamer->overlayChannel())
            ->where('authEndpoint', route('overlays.broadcasting.auth', ['token' => OVERLAY_TOKEN]))
            ->where('settings', $this->streamer->settings->forScene($scene)->toArray())
            ->where('recentChat', [])
            ->where('session', expected: null));
})->with([
    'coworking' => [OverlayScene::Coworking, 'Overlays/Coworking'],
    'a live vai começar' => [OverlayScene::StartingSoon, 'Overlays/StartingSoon'],
    'sala de voz' => [OverlayScene::Voice, 'Overlays/Voice'],
]);

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
