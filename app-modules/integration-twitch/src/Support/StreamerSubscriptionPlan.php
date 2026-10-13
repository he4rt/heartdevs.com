<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Support;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\OAuth\TwitchBotTokenService;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final class StreamerSubscriptionPlan
{
    private const array ALERT_TYPES = [
        TwitchEventSubType::StreamOnline,
        TwitchEventSubType::StreamOffline,
        TwitchEventSubType::ChannelUpdate,
        TwitchEventSubType::ChannelFollow,
        TwitchEventSubType::ChannelSubscribe,
        TwitchEventSubType::ChannelSubscriptionMessage,
        TwitchEventSubType::ChannelSubscriptionGift,
        TwitchEventSubType::ChannelCheer,
        TwitchEventSubType::ChannelRaid,
    ];

    private const array CHAT_TYPES = [
        TwitchEventSubType::ChannelChatMessage,
        TwitchEventSubType::ChannelChatMessageDelete,
        TwitchEventSubType::ChannelChatClear,
        TwitchEventSubType::ChannelChatClearUserMessages,
    ];

    /**
     * @return array<string, array{TwitchEventSubType, array<string, string>}>
     */
    public static function for(StreamerSource $source): array
    {
        $broadcasterId = $source->identity->external_account_id;
        $isSyncable = $source->identity->provider === IdentityProvider::Twitch
            && $source->streamer->isActive()
            && $source->identity()->activelyConnected()->exists();

        if (!$isSyncable || $broadcasterId === null) {
            return [];
        }

        $chatUserId = match ($source->chat_reader) {
            ChatReader::OwnAccount => $broadcasterId,
            ChatReader::He4rtBot => TwitchBotTokenService::userId(),
            null => null,
        };

        $wanted = array_map(fn (TwitchEventSubType $type): array => [$type, $type->getCondition($broadcasterId)], self::ALERT_TYPES);

        if ($chatUserId !== null) {
            foreach (self::CHAT_TYPES as $type) {
                $wanted[] = [$type, $type->getCondition($broadcasterId, $chatUserId)];
            }
        }

        $desired = [];

        foreach ($wanted as [$type, $condition]) {
            $desired[self::keyOf($type->value, $condition)] = [$type, $condition];
        }

        return $desired;
    }

    /**
     * Twitch echoes unused condition fields as empty strings, so they are dropped before comparing.
     *
     * @param  array<string, mixed>  $condition
     */
    public static function keyOf(string $type, array $condition): string
    {
        $filled = array_filter($condition, fn (mixed $value): bool => is_string($value) && $value !== '');
        ksort($filled);

        return $type.'|'.json_encode($filled, JSON_THROW_ON_ERROR);
    }
}
