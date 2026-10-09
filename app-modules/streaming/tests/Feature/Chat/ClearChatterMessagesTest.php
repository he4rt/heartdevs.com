<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Broadcasting\ChatterMessagesCleared;
use He4rt\Streaming\Chat\Actions\ClearChatterMessages;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([ChatterMessagesCleared::class]);

    $this->source = StreamerSource::factory()->readingChat()->create();
    $this->broadcasterId = $this->source->identity->external_account_id;
    $this->spammer = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch, 'external_account_id' => '9911']);
});

function spammerMessage(string $channelId, string $identityId, CarbonImmutable $sentAt): Message
{
    return Message::factory()->create([
        'platform' => IdentityProvider::Twitch,
        'channel_id' => $channelId,
        'external_identity_id' => $identityId,
        'sent_at' => $sentAt,
        'metadata' => new ChatMessageMetadata('Spammer')->toArray(),
    ]);
}

function isDeletedMessage(Message $message): bool
{
    return ChatMessageMetadata::fromArray($message->refresh()->metadata ?? [])->isDeleted();
}

test('o ban marca as mensagens das últimas 24 horas do chatter e tira as linhas da overlay', function (): void {
    $now = CarbonImmutable::now();
    $recent = spammerMessage($this->broadcasterId, $this->spammer->getKey(), $now->subMinutes(3));
    $yesterday = spammerMessage($this->broadcasterId, $this->spammer->getKey(), $now->subHours(30));

    resolve(ClearChatterMessages::class)->handle(IdentityProvider::Twitch, $this->broadcasterId, '9911', $now);

    expect(isDeletedMessage($recent))->toBeTrue()
        ->and(isDeletedMessage($yesterday))->toBeFalse();
    Event::assertDispatched(fn (ChatterMessagesCleared $broadcast): bool => $broadcast->broadcastAs() === 'chat.chatter-cleared'
        && $broadcast->broadcastWith() === ['chatterId' => '9911']);
});

test('as mensagens do chatter em outro canal não mudam', function (): void {
    $elsewhere = spammerMessage('227168488', $this->spammer->getKey(), CarbonImmutable::now()->subMinute());

    resolve(ClearChatterMessages::class)->handle(IdentityProvider::Twitch, $this->broadcasterId, '9911', CarbonImmutable::now());

    expect(isDeletedMessage($elsewhere))->toBeFalse();
});

test('um chatter sem mensagens gravadas ainda tira as linhas da overlay', function (): void {
    resolve(ClearChatterMessages::class)->handle(IdentityProvider::Twitch, $this->broadcasterId, '404404', CarbonImmutable::now());

    Event::assertDispatched(ChatterMessagesCleared::class);
});

test('uma fonte desligada não manda nada para a overlay', function (): void {
    $this->source->update(['enabled' => false]);
    $recent = spammerMessage($this->broadcasterId, $this->spammer->getKey(), CarbonImmutable::now()->subMinute());

    resolve(ClearChatterMessages::class)->handle(IdentityProvider::Twitch, $this->broadcasterId, '9911', CarbonImmutable::now());

    expect(isDeletedMessage($recent))->toBeTrue();
    Event::assertNotDispatched(ChatterMessagesCleared::class);
});
