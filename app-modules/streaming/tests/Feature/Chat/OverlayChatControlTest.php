<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Broadcasting\ChatMessageDeleted;
use He4rt\Streaming\Broadcasting\ChatMessageReceived;
use He4rt\Streaming\Broadcasting\ChatterMessagesCleared;
use He4rt\Streaming\Chat\Actions\HideChatMessageFromOverlay;
use He4rt\Streaming\Chat\Actions\MuteChatterOnOverlay;
use He4rt\Streaming\Chat\Actions\RecordChatMessage;
use He4rt\Streaming\Chat\Actions\UnmuteChatterOnOverlay;
use He4rt\Streaming\Chat\Data\ChatFragment;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\DTOs\IncomingChatMessage;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([ChatMessageDeleted::class, ChatMessageReceived::class, ChatterMessagesCleared::class]);

    $this->source = StreamerSource::factory()->readingChat()->create();
    $this->streamer = $this->source->streamer;
    $this->spammer = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch, 'external_account_id' => '9911']);
});

function controlledMessage(StreamerSource $source, ExternalIdentity $chatter, string $providerMessageId): Message
{
    return Message::factory()->create([
        'platform' => IdentityProvider::Twitch,
        'channel_id' => $source->identity->external_account_id,
        'external_identity_id' => $chatter->getKey(),
        'provider_message_id' => $providerMessageId,
        'metadata' => new ChatMessageMetadata('Spammer')->toArray(),
    ]);
}

function incomingFrom(StreamerSource $source, string $chatterId, string $providerMessageId): IncomingChatMessage
{
    return new IncomingChatMessage(
        platform: IdentityProvider::Twitch,
        broadcasterId: $source->identity->external_account_id,
        providerMessageId: $providerMessageId,
        chatterId: $chatterId,
        chatterLogin: 'spammer',
        content: 'compre seguidores',
        sentAt: CarbonImmutable::now(),
        metadata: new ChatMessageMetadata('Spammer', fragments: [ChatFragment::text('compre seguidores')]),
    );
}

test('ocultar tira a mensagem da overlay sem marcar como apagada pela moderação', function (): void {
    $message = controlledMessage($this->source, $this->spammer, 'msg-1');

    resolve(HideChatMessageFromOverlay::class)->handle($this->streamer, $message->id, CarbonImmutable::now());

    $metadata = ChatMessageMetadata::fromArray($message->refresh()->metadata ?? []);

    expect($metadata->isHiddenOnOverlay())->toBeTrue()
        ->and($metadata->isDeleted())->toBeFalse();
    Event::assertDispatched(fn (ChatMessageDeleted $broadcast): bool => $broadcast->msgId === 'msg-1');
});

test('a mensagem do canal de outro streamer não pode ser ocultada', function (): void {
    $otherSource = StreamerSource::factory()->readingChat()->create();
    $message = controlledMessage($otherSource, $this->spammer, 'msg-2');

    expect(fn () => resolve(HideChatMessageFromOverlay::class)->handle($this->streamer, $message->id, CarbonImmutable::now()))
        ->toThrow(ModelNotFoundException::class);
    expect(ChatMessageMetadata::fromArray($message->refresh()->metadata ?? [])->isHiddenOnOverlay())->toBeFalse();
});

test('silenciar guarda o chatter e tira as linhas dele da overlay', function (): void {
    $message = controlledMessage($this->source, $this->spammer, 'msg-1');

    $chatter = resolve(MuteChatterOnOverlay::class)->handle($this->streamer, $message->id, CarbonImmutable::now());

    expect($chatter->chatterId)->toBe('9911')
        ->and($chatter->displayName)->toBe('Spammer')
        ->and($this->streamer->refresh()->settings->mutedChatters->contains(IdentityProvider::Twitch, '9911'))->toBeTrue();
    Event::assertDispatched(fn (ChatterMessagesCleared $broadcast): bool => $broadcast->chatterId === '9911');
});

test('a mensagem nova de um silenciado é gravada, mas não vai para a overlay', function (): void {
    resolve(MuteChatterOnOverlay::class)->handle($this->streamer, controlledMessage($this->source, $this->spammer, 'msg-1')->id, CarbonImmutable::now());

    $recorded = resolve(RecordChatMessage::class)->handle(incomingFrom($this->source, '9911', 'msg-2'));

    expect($recorded)->not->toBeNull();
    Event::assertNotDispatched(ChatMessageReceived::class);
});

test('tirar o silêncio devolve as próximas mensagens à overlay', function (): void {
    resolve(MuteChatterOnOverlay::class)->handle($this->streamer, controlledMessage($this->source, $this->spammer, 'msg-1')->id, CarbonImmutable::now());

    resolve(UnmuteChatterOnOverlay::class)->handle($this->streamer->refresh(), IdentityProvider::Twitch, '9911');
    resolve(RecordChatMessage::class)->handle(incomingFrom($this->source, '9911', 'msg-2'));

    expect($this->streamer->refresh()->settings->mutedChatters->isEmpty())->toBeTrue();
    Event::assertDispatched(ChatMessageReceived::class);
});
