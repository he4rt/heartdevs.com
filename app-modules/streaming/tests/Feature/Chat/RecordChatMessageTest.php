<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Models\Message;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Streaming\Broadcasting\ChatMessageReceived;
use He4rt\Streaming\Chat\Actions\RecordChatMessage;
use He4rt\Streaming\Chat\Data\ChatFragment;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\DTOs\IncomingChatMessage;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([ChatMessageReceived::class]);
});

function chatMessageFor(StreamerSource $source, array $overrides = []): IncomingChatMessage
{
    return new IncomingChatMessage(...[
        'platform' => IdentityProvider::Twitch,
        'broadcasterId' => $source->identity->external_account_id,
        'providerMessageId' => 'msg-1',
        'chatterId' => '9911',
        'chatterLogin' => 'mariacoda',
        'content' => 'boa noite!',
        'sentAt' => CarbonImmutable::now(),
        'metadata' => new ChatMessageMetadata(
            displayName: 'MariaCoda',
            color: '#8b2fe8',
            fragments: [ChatFragment::text('boa noite!')],
        ),
        ...$overrides,
    ]);
}

test('o espectador novo vira identidade solta e a mensagem vai para a atividade com XP 0', function (): void {
    $source = StreamerSource::factory()->readingChat()->create();
    $usersBefore = User::query()->count();
    $charactersBefore = Character::query()->count();

    $message = resolve(RecordChatMessage::class)->handle(chatMessageFor($source));

    $chatter = ExternalIdentity::query()->where('provider', IdentityProvider::Twitch)->where('external_account_id', '9911')->sole();

    expect($chatter->model_id)->toBeNull()
        ->and(User::query()->count())->toBe($usersBefore)
        ->and(Character::query()->count())->toBe($charactersBefore)
        ->and($message?->external_identity_id)->toBe($chatter->getKey())
        ->and($message?->platform)->toBe(IdentityProvider::Twitch)
        ->and($message?->channel_id)->toBe($source->identity->external_account_id)
        ->and($message?->obtained_experience)->toBe(0)
        ->and(ChatMessageMetadata::fromArray($message?->metadata ?? [])->displayName)->toBe('MariaCoda');
    Event::assertDispatched(fn (ChatMessageReceived $broadcast): bool => $broadcast->broadcastAs() === 'chat.message'
        && $broadcast->broadcastWith() === [
            'msgId' => 'msg-1',
            'chatterId' => '9911',
            'username' => 'MariaCoda',
            'color' => '#8b2fe8',
            'badges' => [],
            'fragments' => [['kind' => 'text', 'text' => 'boa noite!']],
        ]);
});

test('o membro da He4rt no chat usa a própria identidade', function (): void {
    $source = StreamerSource::factory()->readingChat()->create();
    $member = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::Twitch,
        'external_account_id' => '9911',
    ]);

    $message = resolve(RecordChatMessage::class)->handle(chatMessageFor($source));

    expect($message?->external_identity_id)->toBe($member->getKey())
        ->and(ExternalIdentity::query()->where('external_account_id', '9911')->count())->toBe(1);
});

test('a mesma mensagem duas vezes grava uma só e avisa uma vez', function (): void {
    $source = StreamerSource::factory()->readingChat()->create();
    $record = resolve(RecordChatMessage::class);

    $record->handle(chatMessageFor($source));
    $record->handle(chatMessageFor($source));

    expect(Message::query()->where('provider_message_id', 'msg-1')->count())->toBe(1);
    Event::assertDispatchedTimes(ChatMessageReceived::class, 1);
});

test('a fonte desligada grava a mensagem sem mandar para a overlay', function (): void {
    $source = StreamerSource::factory()->disabled()->readingChat()->create();

    $message = resolve(RecordChatMessage::class)->handle(chatMessageFor($source));

    expect($message)->not->toBeNull();
    Event::assertNotDispatched(ChatMessageReceived::class);
});

test('a fonte sem leitor de chat grava a mensagem sem mandar para a overlay', function (): void {
    $source = StreamerSource::factory()->create();

    resolve(RecordChatMessage::class)->handle(chatMessageFor($source));

    Event::assertNotDispatched(ChatMessageReceived::class);
});

test('várias mensagens do mesmo espectador criam uma identidade solta só', function (): void {
    $source = StreamerSource::factory()->readingChat(ChatReader::He4rtBot)->create();
    $record = resolve(RecordChatMessage::class);

    $record->handle(chatMessageFor($source, ['providerMessageId' => 'msg-1']));
    $record->handle(chatMessageFor($source, ['providerMessageId' => 'msg-2']));

    expect(ExternalIdentity::query()->where('external_account_id', '9911')->whereNull('model_id')->count())->toBe(1)
        ->and(Message::query()->count())->toBe(2);
});

test('o chat de um canal sem streamer não é gravado', function (): void {
    $message = resolve(RecordChatMessage::class)->handle(new IncomingChatMessage(
        platform: IdentityProvider::Twitch,
        broadcasterId: '227168488',
        providerMessageId: 'msg-1',
        chatterId: '9911',
        chatterLogin: 'mariacoda',
        content: 'boa noite!',
        sentAt: CarbonImmutable::now(),
        metadata: new ChatMessageMetadata('MariaCoda'),
    ));

    expect($message)->toBeNull()
        ->and(ExternalIdentity::query()->where('external_account_id', '9911')->exists())->toBeFalse();
});
