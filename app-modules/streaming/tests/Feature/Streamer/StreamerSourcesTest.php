<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSource;
use He4rt\Streaming\Streamer\Events\StreamerSourceRegistered;
use He4rt\Streaming\Streamer\Events\StreamerSourceUpdated;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Event;

function streamingIdentityOf(User $user, IdentityProvider $provider): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_id' => $user->getKey(),
        'provider' => $provider,
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);
}

test('conectar a Twitch registra uma fonte ligada e sem leitor de chat', function (): void {
    Event::fake([StreamerSourceRegistered::class]);
    $streamer = Streamer::factory()->create();

    event(new ExternalIdentityConnected(streamingIdentityOf($streamer->user, IdentityProvider::Twitch)));

    $source = $streamer->sources()->sole();

    expect($source->enabled)->toBeTrue()
        ->and($source->chat_reader)->toBeNull();
    Event::assertDispatchedTimes(StreamerSourceRegistered::class, 1);
});

test('conectar uma conta que não é de live não registra fonte', function (): void {
    $streamer = Streamer::factory()->create();

    event(new ExternalIdentityConnected(streamingIdentityOf($streamer->user, IdentityProvider::GitHub)));

    expect($streamer->sources()->exists())->toBeFalse();
});

test('conectar a Twitch sem ser streamer não registra fonte', function (): void {
    $user = User::factory()->create();

    event(new ExternalIdentityConnected(streamingIdentityOf($user, IdentityProvider::Twitch)));

    expect(StreamerSource::query()->exists())->toBeFalse();
});

test('o primeiro acesso registra as contas de live já conectadas', function (): void {
    $user = User::factory()->streamer()->create();
    $twitch = streamingIdentityOf($user, IdentityProvider::Twitch);
    streamingIdentityOf($user, IdentityProvider::GitHub);

    $streamer = resolve(EnsureStreamer::class)->handle($user);

    expect($streamer->sources()->pluck('external_identity_id')->all())->toBe([$twitch->getKey()]);
});

test('reconectar não duplica a fonte nem religa a fonte desligada', function (): void {
    Event::fake([StreamerSourceRegistered::class]);
    $source = StreamerSource::factory()->disabled()->create();

    event(new ExternalIdentityConnected($source->identity));

    expect(StreamerSource::query()->count())->toBe(1)
        ->and($source->refresh()->enabled)->toBeFalse();
    Event::assertNotDispatched(StreamerSourceRegistered::class);
});

test('reconectar restaura a fonte apagada', function (): void {
    $source = StreamerSource::factory()->create();
    $source->delete();

    event(new ExternalIdentityConnected($source->identity));

    expect($source->refresh()->trashed())->toBeFalse()
        ->and(StreamerSource::withTrashed()->count())->toBe(1);
});

test('desligar a fonte avisa a mudança', function (): void {
    Event::fake([StreamerSourceUpdated::class]);
    $source = StreamerSource::factory()->create();

    resolve(UpdateStreamerSource::class)->handle($source, enabled: false, chatReader: ChatReader::He4rtBot);

    expect($source->refresh()->enabled)->toBeFalse()
        ->and($source->chat_reader)->toBe(ChatReader::He4rtBot);
    Event::assertDispatchedTimes(StreamerSourceUpdated::class, 1);
});

test('salvar a fonte sem mudança não avisa nada', function (): void {
    Event::fake([StreamerSourceUpdated::class]);
    $source = StreamerSource::factory()->create();

    resolve(UpdateStreamerSource::class)->handle($source, enabled: true, chatReader: null);

    Event::assertNotDispatched(StreamerSourceUpdated::class);
});
