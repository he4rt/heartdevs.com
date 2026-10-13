<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\Streaming\Chat\Actions\ClearChat;
use He4rt\Streaming\Chat\Actions\HideChatMessageFromOverlay;
use He4rt\Streaming\Chat\Actions\MuteChatterOnOverlay;
use He4rt\Streaming\Chat\Actions\UnmuteChatterOnOverlay;
use He4rt\Streaming\Chat\Data\ChatBadge;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Chat\Queries\StreamerChatMessages;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;

class StreamChatPage extends Page
{
    private const int CHAT_LIMIT = 50;

    protected static ?string $cluster = StreamingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chat';

    protected static ?string $title = 'Chat da live';

    protected static ?string $slug = 'chat';

    protected static ?int $navigationSort = 3;

    protected string $view = 'panel-app::pages.streaming.chat';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('use-streamer-tools') ?? false;
    }

    public function mount(EnsureStreamer $ensureStreamer): void
    {
        $ensureStreamer->handle($this->currentUser());
    }

    public function hideMessageAction(): Action
    {
        return Action::make('hideMessage')
            ->label('Ocultar na overlay')
            ->tooltip('Ocultar na overlay. Na Twitch a mensagem continua.')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->iconButton()
            ->color('gray')
            ->size('sm')
            ->action(function (array $arguments, HideChatMessageFromOverlay $hideMessage): void {
                $hideMessage->handle($this->currentStreamer(), $this->messageIdOf($arguments), CarbonImmutable::now());

                Notification::make()
                    ->title('Mensagem oculta na overlay')
                    ->body('Na Twitch ela continua.')
                    ->success()
                    ->send();
            });
    }

    public function muteChatterAction(): Action
    {
        return Action::make('muteChatter')
            ->label('Silenciar na overlay')
            ->tooltip('Silenciar na overlay. Na Twitch nada muda.')
            ->icon(Heroicon::OutlinedSpeakerXMark)
            ->iconButton()
            ->color('gray')
            ->size('sm')
            ->action(function (array $arguments, MuteChatterOnOverlay $muteChatter): void {
                $chatter = $muteChatter->handle($this->currentStreamer(), $this->messageIdOf($arguments), CarbonImmutable::now());

                Notification::make()
                    ->title(sprintf('%s silenciado na overlay', $chatter->displayName))
                    ->body('As mensagens continuam na Twitch e aqui no painel.')
                    ->success()
                    ->send();
            });
    }

    public function unmuteChatterAction(): Action
    {
        return Action::make('unmuteChatter')
            ->label('Tirar')
            ->color('gray')
            ->size('sm')
            ->action(function (array $arguments, UnmuteChatterOnOverlay $unmuteChatter): void {
                $platform = is_string($arguments['platform'] ?? null) ? IdentityProvider::tryFrom($arguments['platform']) : null;
                $chatterId = $arguments['chatter'] ?? null;

                if (!$platform instanceof IdentityProvider || !is_string($chatterId)) {
                    return;
                }

                $unmuteChatter->handle($this->currentStreamer(), $platform, $chatterId);

                Notification::make()
                    ->title('Silêncio retirado')
                    ->body('As próximas mensagens aparecem na overlay.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return Action[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearOverlayChat')
                ->label('Limpar chat da overlay')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Limpar o chat da overlay?')
                ->modalDescription('As mensagens somem da overlay. Na Twitch e aqui no painel elas continuam.')
                ->modalSubmitActionLabel('Limpar')
                ->action(function (ClearChat $clearChat): void {
                    $clearChat->handle($this->currentStreamer(), CarbonImmutable::now());

                    Notification::make()
                        ->title('Chat da overlay limpo')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $streamer = $this->currentStreamer();
        $mutedChatters = $streamer->settings->mutedChatters;
        $hasChatSource = $streamer->sources()->get()->contains(fn (StreamerSource $source): bool => $source->showsChat());

        $lines = resolve(StreamerChatMessages::class)->of($streamer)
            ->with('provider')
            ->latest('sent_at')
            ->limit(self::CHAT_LIMIT)
            ->get()
            ->map(function (Message $message) use ($mutedChatters, $streamer): array {
                $metadata = ChatMessageMetadata::fromArray($message->metadata ?? []);
                $chatterId = $message->provider->external_account_id ?? '';
                $sentAt = $message->sent_at ?? $message->created_at;

                return [
                    'id' => $message->id,
                    'at' => $sentAt,
                    'username' => $metadata->displayName !== '' ? $metadata->displayName : $chatterId,
                    'color' => $metadata->color,
                    'badges' => array_values(array_filter(array_map(fn (ChatBadge $badge): ?string => $badge->url, $metadata->badges))),
                    'text' => $message->content,
                    'isDeleted' => $metadata->isDeleted(),
                    'isHidden' => $metadata->isHiddenOnOverlay(),
                    'isMuted' => $mutedChatters->contains($message->platform, $chatterId),
                    'isBeforeClear' => $streamer->chat_cleared_at !== null && $sentAt !== null && $sentAt->lte($streamer->chat_cleared_at),
                ];
            })
            ->all();

        return [
            'hasChatSource' => $hasChatSource,
            'lines' => $lines,
            'clearedAt' => $streamer->chat_cleared_at,
            'mutedChatters' => $mutedChatters->chatters,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function messageIdOf(array $arguments): string
    {
        $messageId = $arguments['message'] ?? null;

        return is_string($messageId) ? $messageId : '';
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function currentStreamer(): Streamer
    {
        return Streamer::query()->whereBelongsTo($this->currentUser())->sole();
    }
}
