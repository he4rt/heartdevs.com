<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\ETL;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\Streaming\Chat\Data\ChatBadge;
use He4rt\Streaming\Chat\Data\ChatFragment;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\DTOs\IncomingChatMessage;
use He4rt\Streaming\DTOs\IncomingSessionChange;
use He4rt\Streaming\DTOs\IncomingStreamEvent;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\SubTier;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\StreamActor;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;

final readonly class TwitchStreamingPayloadMapper
{
    private const string EMOTE_URL = 'https://static-cdn.jtvnw.net/emoticons/v2/%s/default/dark/1.0';

    /**
     * @param  array<array-key, mixed>  $stream  the first item of Helix GET /streams
     */
    public function sessionStarted(TwitchEventLog $log, array $stream = []): ?IncomingSessionChange
    {
        $event = $this->event($log);

        return $this->sessionChange($log, [
            'at' => $this->timeOf($event, 'started_at') ?? $this->receivedAt($log),
            'platformStreamId' => $this->stringOf($event, 'id'),
            'title' => $this->stringOf($stream, 'title'),
            'category' => $this->stringOf($stream, 'game_name'),
        ]);
    }

    public function sessionUpdated(TwitchEventLog $log): ?IncomingSessionChange
    {
        $event = $this->event($log);

        return $this->sessionChange($log, [
            'at' => $this->receivedAt($log),
            'title' => $this->stringOf($event, 'title'),
            'category' => $this->stringOf($event, 'category_name'),
        ]);
    }

    public function sessionEnded(TwitchEventLog $log): ?IncomingSessionChange
    {
        return $this->sessionChange($log, ['at' => $this->receivedAt($log)]);
    }

    public function streamEvent(TwitchEventLog $log): ?IncomingStreamEvent
    {
        $event = $this->event($log);
        $tier = SubTier::tryFrom($this->stringOf($event, 'tier') ?? '') ?? SubTier::Tier1;
        $isAnonymous = ($event['is_anonymous'] ?? false) === true;
        $isGiftedSub = ($event['is_gift'] ?? false) === true;

        return match (TwitchEventSubType::tryFrom($log->event_type)) {
            TwitchEventSubType::ChannelFollow => $this->incomingEvent($log, StreamEventType::Follow, $this->actorOf($event, 'user'), occurredAt: $this->timeOf($event, 'followed_at')),
            TwitchEventSubType::ChannelSubscribe => $isGiftedSub ? null : $this->incomingEvent($log, StreamEventType::Sub, $this->actorOf($event, 'user'), new SubDetails($tier)),
            TwitchEventSubType::ChannelSubscriptionMessage => $this->incomingEvent($log, StreamEventType::Sub, $this->actorOf($event, 'user'), new SubDetails(
                tier: $tier,
                months: max(1, $this->intOf($event, 'cumulative_months')),
                message: $this->stringOf($event, 'message.text'),
            )),
            TwitchEventSubType::ChannelSubscriptionGift => $this->incomingEvent($log, StreamEventType::GiftSub, $isAnonymous ? null : $this->actorOf($event, 'user'), new GiftSubDetails(
                tier: $tier,
                total: max(1, $this->intOf($event, 'total')),
            )),
            TwitchEventSubType::ChannelCheer => $this->incomingEvent($log, StreamEventType::Cheer, $isAnonymous ? null : $this->actorOf($event, 'user'), new CheerDetails(
                bits: $this->intOf($event, 'bits'),
                message: $this->stringOf($event, 'message'),
            )),
            TwitchEventSubType::ChannelRaid => $this->incomingEvent($log, StreamEventType::Raid, $this->actorOf($event, 'from_broadcaster_user'), new RaidDetails(
                viewers: $this->intOf($event, 'viewers'),
            )),
            default => null,
        };
    }

    /**
     * @param  array<string, string>  $badgeUrls  image URL keyed by "set_id/version", from TwitchChatBadgeCatalog
     */
    public function chatMessage(TwitchEventLog $log, array $badgeUrls = []): ?IncomingChatMessage
    {
        $event = $this->event($log);
        $messageId = $this->stringOf($event, 'message_id');
        $chatterId = $this->stringOf($event, 'chatter_user_id');
        $chatterLogin = $this->stringOf($event, 'chatter_user_login');
        $isComplete = $log->broadcaster_user_id !== null && $messageId !== null && $chatterId !== null && $chatterLogin !== null;

        if (!$isComplete) {
            return null;
        }

        return new IncomingChatMessage(
            platform: IdentityProvider::Twitch,
            broadcasterId: $log->broadcaster_user_id,
            providerMessageId: $messageId,
            chatterId: $chatterId,
            chatterLogin: $chatterLogin,
            content: $this->stringOf($event, 'message.text') ?? '',
            sentAt: $this->receivedAt($log),
            metadata: new ChatMessageMetadata(
                displayName: $this->stringOf($event, 'chatter_user_name') ?? $chatterLogin,
                color: $this->stringOf($event, 'color'),
                badges: $this->badgesOf($event, $badgeUrls),
                fragments: $this->fragmentsOf($event),
            ),
        );
    }

    public function hasChatBadges(TwitchEventLog $log): bool
    {
        return $this->listOf($this->event($log), 'badges') !== [];
    }

    public function deletedMessageId(TwitchEventLog $log): ?string
    {
        return $this->stringOf($this->event($log), 'message_id');
    }

    public function clearedChatterId(TwitchEventLog $log): ?string
    {
        return $this->stringOf($this->event($log), 'target_user_id');
    }

    public function receivedAt(TwitchEventLog $log): CarbonImmutable
    {
        return $log->created_at instanceof CarbonInterface
            ? CarbonImmutable::instance($log->created_at)
            : CarbonImmutable::now();
    }

    /**
     * @param  array{at: CarbonImmutable, platformStreamId?: string|null, title?: string|null, category?: string|null}  $fields
     */
    private function sessionChange(TwitchEventLog $log, array $fields): ?IncomingSessionChange
    {
        if ($log->broadcaster_user_id === null) {
            return null;
        }

        return new IncomingSessionChange(
            platform: IdentityProvider::Twitch,
            broadcasterId: $log->broadcaster_user_id,
            at: $fields['at'],
            platformStreamId: $fields['platformStreamId'] ?? null,
            title: $fields['title'] ?? null,
            category: $fields['category'] ?? null,
        );
    }

    private function incomingEvent(
        TwitchEventLog $log,
        StreamEventType $type,
        ?StreamActor $actor,
        ?StreamEventDetails $details = null,
        ?CarbonImmutable $occurredAt = null,
    ): ?IncomingStreamEvent {
        $hasOrigin = $log->broadcaster_user_id !== null && $log->twitch_message_id !== null;

        if (!$hasOrigin) {
            return null;
        }

        return new IncomingStreamEvent(
            platform: IdentityProvider::Twitch,
            broadcasterId: $log->broadcaster_user_id,
            sourceEventId: $log->twitch_message_id,
            type: $type,
            occurredAt: $occurredAt ?? $this->receivedAt($log),
            actor: $actor,
            details: $details,
        );
    }

    /**
     * @param  array<array-key, mixed>  $event
     */
    private function actorOf(array $event, string $prefix): ?StreamActor
    {
        $platformId = $this->stringOf($event, $prefix.'_id');
        $login = $this->stringOf($event, $prefix.'_login');

        if ($platformId === null || $login === null) {
            return null;
        }

        return new StreamActor(
            platformId: $platformId,
            login: $login,
            displayName: $this->stringOf($event, $prefix.'_name') ?? $login,
        );
    }

    /**
     * @param  array<array-key, mixed>  $event
     * @param  array<string, string>  $badgeUrls
     * @return list<ChatBadge>
     */
    private function badgesOf(array $event, array $badgeUrls): array
    {
        $badges = [];

        foreach ($this->listOf($event, 'badges') as $badge) {
            $setId = $this->stringOf($badge, 'set_id');
            $version = $this->stringOf($badge, 'id');

            if ($setId !== null && $version !== null) {
                $badges[] = new ChatBadge($setId, $version, $badgeUrls[$setId.'/'.$version] ?? null);
            }
        }

        return $badges;
    }

    /**
     * @param  array<array-key, mixed>  $event
     * @return list<ChatFragment>
     */
    private function fragmentsOf(array $event): array
    {
        $fragments = [];

        foreach ($this->listOf($event, 'message.fragments') as $fragment) {
            $text = $this->stringOf($fragment, 'text') ?? '';
            $emoteId = $this->stringOf($fragment, 'emote.id');
            $isEmote = $this->stringOf($fragment, 'type') === 'emote' && $emoteId !== null;

            $fragments[] = $isEmote
                ? ChatFragment::emote($text, $emoteId, sprintf(self::EMOTE_URL, $emoteId))
                : ChatFragment::text($text);
        }

        return $fragments;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function event(TwitchEventLog $log): array
    {
        $event = $log->payload['event'] ?? null;

        return is_array($event) ? $event : [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<array<array-key, mixed>>
     */
    private function listOf(array $data, string $key): array
    {
        $items = data_get($data, $key);

        return is_array($items) ? array_values(array_filter($items, is_array(...))) : [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function stringOf(array $data, string $key): ?string
    {
        $value = data_get($data, $key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function intOf(array $data, string $key): int
    {
        $value = data_get($data, $key);

        return is_int($value) ? $value : 0;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function timeOf(array $data, string $key): ?CarbonImmutable
    {
        $value = $this->stringOf($data, $key);

        return $value === null ? null : CarbonImmutable::parse($value);
    }
}
