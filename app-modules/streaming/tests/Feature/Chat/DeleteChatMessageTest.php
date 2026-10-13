<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Broadcasting\ChatMessageDeleted;
use He4rt\Streaming\Chat\Actions\DeleteChatMessage;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([ChatMessageDeleted::class]);

    $this->source = StreamerSource::factory()->readingChat()->create();
    $this->broadcasterId = $this->source->identity->external_account_id;
});

test('a mensagem apagada pela moderação fica no banco e sai da overlay', function (): void {
    $message = Message::factory()->create([
        'platform' => IdentityProvider::Twitch,
        'channel_id' => $this->broadcasterId,
        'provider_message_id' => 'msg-1',
        'metadata' => new ChatMessageMetadata('MariaCoda')->toArray(),
    ]);

    resolve(DeleteChatMessage::class)->handle(IdentityProvider::Twitch, $this->broadcasterId, 'msg-1', CarbonImmutable::now());

    $metadata = ChatMessageMetadata::fromArray($message->refresh()->metadata ?? []);

    expect($message->exists)->toBeTrue()
        ->and($metadata->isDeleted())->toBeTrue()
        ->and($metadata->displayName)->toBe('MariaCoda');
    Event::assertDispatched(fn (ChatMessageDeleted $broadcast): bool => $broadcast->broadcastWith() === ['msgId' => 'msg-1']
        && $broadcast->broadcastAs() === 'chat.message-deleted');
});

test('uma mensagem desconhecida não muda nada', function (): void {
    resolve(DeleteChatMessage::class)->handle(IdentityProvider::Twitch, $this->broadcasterId, 'msg-404', CarbonImmutable::now());

    Event::assertNotDispatched(ChatMessageDeleted::class);
});

test('a mensagem de outro canal não é marcada', function (): void {
    $message = Message::factory()->create([
        'platform' => IdentityProvider::Twitch,
        'channel_id' => '227168488',
        'provider_message_id' => 'msg-1',
    ]);

    resolve(DeleteChatMessage::class)->handle(IdentityProvider::Twitch, $this->broadcasterId, 'msg-1', CarbonImmutable::now());

    expect($message->refresh()->metadata)->toBeNull();
    Event::assertNotDispatched(ChatMessageDeleted::class);
});
