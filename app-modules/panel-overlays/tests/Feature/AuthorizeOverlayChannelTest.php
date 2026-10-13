<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Actions\RegenerateOverlayToken;
use He4rt\Streaming\Streamer\Models\Streamer;

const OVERLAY_SOCKET_ID = '1234.5678';

beforeEach(function (): void {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb', [
        'driver' => 'reverb',
        'key' => 'overlay-key',
        'secret' => 'overlay-secret',
        'app_id' => '1001',
        'options' => ['host' => 'localhost', 'port' => 8_080, 'scheme' => 'http', 'useTLS' => false],
    ]);

    $this->token = '9c1f0a7e3b5d42a8b6e4f1c0d9a7b3e2';
    $this->streamer = Streamer::factory()->withToken($this->token)->create();
});

/**
 * @return array{socket_id: string, channel_name: string}
 */
function overlayAuthRequest(Streamer $streamer, string $socketId = OVERLAY_SOCKET_ID): array
{
    return ['socket_id' => $socketId, 'channel_name' => 'private-'.$streamer->overlayChannel()];
}

test('o token e o canal do streamer liberam a assinatura do canal', function (): void {
    $channel = 'private-'.$this->streamer->overlayChannel();

    $this->post(route('overlays.broadcasting.auth', ['token' => $this->token]), overlayAuthRequest($this->streamer))
        ->assertOk()
        ->assertExactJson(['auth' => 'overlay-key:'.hash_hmac('sha256', OVERLAY_SOCKET_ID.':'.$channel, 'overlay-secret')]);
});

test('o token não libera o canal de outro streamer', function (): void {
    $other = Streamer::factory()->create();

    $this->post(route('overlays.broadcasting.auth', ['token' => $this->token]), overlayAuthRequest($other))
        ->assertForbidden();
});

test('a overlay antiga perde o canal quando o token é regenerado', function (): void {
    $oldChannelRequest = overlayAuthRequest($this->streamer);

    resolve(RegenerateOverlayToken::class)->handle($this->streamer);

    $this->post(route('overlays.broadcasting.auth', ['token' => $this->token]), $oldChannelRequest)->assertForbidden();
    $this->post(route('overlays.broadcasting.auth', ['token' => $this->token]), overlayAuthRequest($this->streamer->refresh()))->assertForbidden();
});

test('o streamer desativado não recebe a assinatura', function (): void {
    $this->streamer->update(['status' => StreamerStatus::Disabled]);

    $this->post(route('overlays.broadcasting.auth', ['token' => $this->token]), overlayAuthRequest($this->streamer))
        ->assertForbidden();
});

test('um socket id fora do formato do Pusher é recusado', function (): void {
    $this->post(route('overlays.broadcasting.auth', ['token' => $this->token]), overlayAuthRequest($this->streamer, socketId: 'abc'))
        ->assertForbidden();
});

test('a autorização tem limite de pedidos por minuto', function (): void {
    $url = route('overlays.broadcasting.auth', ['token' => $this->token]);

    foreach (range(1, 30) as $attempt) {
        $this->post($url, overlayAuthRequest($this->streamer))->assertOk();
    }

    $this->post($url, overlayAuthRequest($this->streamer))->assertTooManyRequests();
});
