<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamChatPage;
use He4rt\Streaming\Broadcasting\ChatCleared;
use He4rt\Streaming\Broadcasting\ChatMessageDeleted;
use He4rt\Streaming\Broadcasting\ChatterMessagesCleared;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Event;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Event::fake([ChatMessageDeleted::class, ChatterMessagesCleared::class, ChatCleared::class]);

    $this->user = User::factory()->streamer()->create();
    $this->streamer = resolve(EnsureStreamer::class)->handle($this->user);
    $this->source = StreamerSource::factory()->readingChat()->create(['streamer_id' => $this->streamer->id]);
    $this->chatter = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch, 'external_account_id' => '9911']);
    $this->actingAs($this->user);
});

function panelChatMessage(StreamerSource $source, ExternalIdentity $chatter, string $text, int $minutesAgo = 1): Message
{
    return Message::factory()->create([
        'platform' => IdentityProvider::Twitch,
        'channel_id' => $source->identity->external_account_id,
        'external_identity_id' => $chatter->getKey(),
        'provider_message_id' => 'msg-'.$text,
        'content' => $text,
        'sent_at' => now()->subMinutes($minutesAgo),
        'metadata' => new ChatMessageMetadata('MariaCoda')->toArray(),
    ]);
}

test('a página mostra as 50 mensagens mais novas do chat da overlay', function (): void {
    foreach (range(1, 55) as $minutesAgo) {
        panelChatMessage($this->source, $this->chatter, 'linha-'.$minutesAgo, $minutesAgo);
    }

    livewire(StreamChatPage::class)
        ->assertOk()
        ->assertSee('linha-1')
        ->assertSee('linha-50')
        ->assertDontSee('linha-51');
});

test('ocultar uma mensagem vale só para a overlay', function (): void {
    $message = panelChatMessage($this->source, $this->chatter, 'spam');

    livewire(StreamChatPage::class)
        ->callAction('hideMessage', arguments: ['message' => $message->id])
        ->assertNotified('Mensagem oculta na overlay')
        ->assertSee('oculta na overlay');

    expect(ChatMessageMetadata::fromArray($message->refresh()->metadata ?? [])->isHiddenOnOverlay())->toBeTrue();
    Event::assertDispatched(ChatMessageDeleted::class);
});

test('silenciar e tirar o silêncio de um chatter', function (): void {
    $message = panelChatMessage($this->source, $this->chatter, 'spam');

    $page = livewire(StreamChatPage::class)
        ->callAction('muteChatter', arguments: ['message' => $message->id])
        ->assertNotified('MariaCoda silenciado na overlay')
        ->assertSee('Silenciados na overlay');

    expect($this->streamer->refresh()->settings->mutedChatters->contains(IdentityProvider::Twitch, '9911'))->toBeTrue();
    Event::assertDispatched(fn (ChatterMessagesCleared $broadcast): bool => $broadcast->chatterId === '9911');

    $page->callAction('unmuteChatter', arguments: ['platform' => 'twitch', 'chatter' => '9911'])
        ->assertNotified('Silêncio retirado');

    expect($this->streamer->refresh()->settings->mutedChatters->isEmpty())->toBeTrue();
});

test('limpar o chat da overlay pede confirmação e não apaga nada', function (): void {
    $message = panelChatMessage($this->source, $this->chatter, 'antes');

    livewire(StreamChatPage::class)
        ->callAction('clearOverlayChat')
        ->assertNotified('Chat da overlay limpo')
        ->assertSee('Chat da overlay limpo às');

    expect($this->streamer->refresh()->chat_cleared_at)->not->toBeNull()
        ->and($message->fresh())->not->toBeNull();
    Event::assertDispatched(ChatCleared::class);
});

test('a mensagem apagada pela moderação aparece marcada e sem botões', function (): void {
    $message = panelChatMessage($this->source, $this->chatter, 'banido');
    $message->update(['metadata' => ChatMessageMetadata::fromArray($message->metadata ?? [])->withDeletedAt(now()->toImmutable())->toArray()]);

    livewire(StreamChatPage::class)
        ->assertSee('apagada pela moderação')
        ->assertDontSee('Ocultar na overlay. Na Twitch a mensagem continua.');
});

test('sem fonte com chat, a página explica onde escolher o leitor', function (): void {
    $this->source->update(['chat_reader' => null]);

    livewire(StreamChatPage::class)
        ->assertSee('Nenhuma fonte mostra chat na overlay');
});

test('a mensagem do canal de outro streamer não pode ser ocultada', function (): void {
    $otherSource = StreamerSource::factory()->readingChat()->create();
    $message = panelChatMessage($otherSource, $this->chatter, 'alheia');

    livewire(StreamChatPage::class)
        ->call('mountAction', 'hideMessage', ['message' => $message->id])
        ->assertStatus(404);

    expect(ChatMessageMetadata::fromArray($message->refresh()->metadata ?? [])->isHiddenOnOverlay())->toBeFalse();
    Event::assertNotDispatched(ChatMessageDeleted::class);
});
