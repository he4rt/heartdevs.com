<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\ETL\Listeners;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\ETL\TwitchChatBadgeCatalog;
use He4rt\IntegrationTwitch\ETL\TwitchStreamingPayloadMapper;
use He4rt\IntegrationTwitch\Events\TwitchEventReceived;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\IntegrationTwitch\Transport\Requests\Streams\GetStreams;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\Streaming\Chat\Actions\ClearChat;
use He4rt\Streaming\Chat\Actions\ClearChatterMessages;
use He4rt\Streaming\Chat\Actions\DeleteChatMessage;
use He4rt\Streaming\Chat\Actions\RecordChatMessage;
use He4rt\Streaming\DTOs\IncomingChatMessage;
use He4rt\Streaming\DTOs\IncomingSessionChange;
use He4rt\Streaming\DTOs\IncomingStreamEvent;
use He4rt\Streaming\Session\Actions\EndStreamSession;
use He4rt\Streaming\Session\Actions\StartStreamSession;
use He4rt\Streaming\Session\Actions\UpdateStreamSession;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Actions\RecordStreamEvent;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\SaloonException;

final readonly class ProjectTwitchEventToStreaming
{
    public function __construct(
        private TwitchStreamingPayloadMapper $mapper,
        private ResolveActiveSource $resolveActiveSource,
        private StartStreamSession $startSession,
        private UpdateStreamSession $updateSession,
        private EndStreamSession $endSession,
        private RecordStreamEvent $recordStreamEvent,
        private RecordChatMessage $recordChatMessage,
        private DeleteChatMessage $deleteChatMessage,
        private ClearChatterMessages $clearChatterMessages,
        private ClearChat $clearChat,
        private TwitchChatBadgeCatalog $badgeCatalog,
    ) {}

    public function handle(TwitchEventReceived $received): void
    {
        $log = $received->eventLog;

        match (TwitchEventSubType::tryFrom($log->event_type)) {
            TwitchEventSubType::StreamOnline => $this->startSession($log),
            TwitchEventSubType::StreamOffline => $this->changeSession($this->mapper->sessionEnded($log), $this->endSession->handle(...)),
            TwitchEventSubType::ChannelUpdate => $this->changeSession($this->mapper->sessionUpdated($log), $this->updateSession->handle(...)),
            TwitchEventSubType::ChannelFollow,
            TwitchEventSubType::ChannelSubscribe,
            TwitchEventSubType::ChannelSubscriptionMessage,
            TwitchEventSubType::ChannelSubscriptionGift,
            TwitchEventSubType::ChannelCheer,
            TwitchEventSubType::ChannelRaid => $this->recordStreamEvent($log),
            TwitchEventSubType::ChannelChatMessage => $this->recordChatMessage($log),
            TwitchEventSubType::ChannelChatMessageDelete => $this->deleteChatMessage($log),
            TwitchEventSubType::ChannelChatClearUserMessages => $this->clearChatterMessages($log),
            TwitchEventSubType::ChannelChatClear => $this->clearChat($log),
            default => null,
        };
    }

    private function startSession(TwitchEventLog $log): void
    {
        $broadcasterId = $log->broadcaster_user_id;

        if ($broadcasterId === null) {
            return;
        }

        $hasActiveSource = $this->resolveActiveSource->handle(IdentityProvider::Twitch, $broadcasterId) instanceof StreamerSource;

        if (!$hasActiveSource) {
            return;
        }

        $change = $this->mapper->sessionStarted($log, $this->currentStream($broadcasterId));

        if ($change instanceof IncomingSessionChange) {
            $this->startSession->handle($change);
        }
    }

    /**
     * @param  callable(IncomingSessionChange): mixed  $action
     */
    private function changeSession(?IncomingSessionChange $change, callable $action): void
    {
        if ($change instanceof IncomingSessionChange) {
            $action($change);
        }
    }

    private function recordStreamEvent(TwitchEventLog $log): void
    {
        $incoming = $this->mapper->streamEvent($log);

        if ($incoming instanceof IncomingStreamEvent) {
            $this->recordStreamEvent->handle($incoming);
        }
    }

    private function recordChatMessage(TwitchEventLog $log): void
    {
        $incoming = $this->mapper->chatMessage($log, $this->badgeUrlsFor($log));

        if ($incoming instanceof IncomingChatMessage) {
            $this->recordChatMessage->handle($incoming);
        }
    }

    /**
     * @return array<string, string>
     */
    private function badgeUrlsFor(TwitchEventLog $log): array
    {
        $broadcasterId = $log->broadcaster_user_id;

        if ($broadcasterId === null || !$this->mapper->hasChatBadges($log)) {
            return [];
        }

        $hasActiveSource = $this->resolveActiveSource->handle(IdentityProvider::Twitch, $broadcasterId) instanceof StreamerSource;

        return $hasActiveSource ? $this->badgeCatalog->forChannel($broadcasterId) : [];
    }

    private function deleteChatMessage(TwitchEventLog $log): void
    {
        $messageId = $this->mapper->deletedMessageId($log);
        $broadcasterId = $log->broadcaster_user_id;

        if ($messageId === null || $broadcasterId === null) {
            return;
        }

        $this->deleteChatMessage->handle(IdentityProvider::Twitch, $broadcasterId, $messageId, $this->mapper->receivedAt($log));
    }

    private function clearChatterMessages(TwitchEventLog $log): void
    {
        $chatterId = $this->mapper->clearedChatterId($log);
        $broadcasterId = $log->broadcaster_user_id;

        if ($chatterId === null || $broadcasterId === null) {
            return;
        }

        $this->clearChatterMessages->handle(IdentityProvider::Twitch, $broadcasterId, $chatterId, $this->mapper->receivedAt($log));
    }

    private function clearChat(TwitchEventLog $log): void
    {
        $broadcasterId = $log->broadcaster_user_id;
        $source = $broadcasterId === null ? null : $this->resolveActiveSource->handle(IdentityProvider::Twitch, $broadcasterId);

        if ($source?->showsChat() === true) {
            $this->clearChat->handle($source->streamer, $this->mapper->receivedAt($log));
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function currentStream(string $broadcasterId): array
    {
        try {
            $stream = resolve(TwitchHelixConnector::class)->send(new GetStreams($broadcasterId))->json('data.0');
        } catch (SaloonException $saloonException) {
            Log::warning('Twitch stream lookup failed', ['broadcaster_id' => $broadcasterId, 'error' => $saloonException->getMessage()]);

            return [];
        }

        return is_array($stream) ? $stream : [];
    }
}
