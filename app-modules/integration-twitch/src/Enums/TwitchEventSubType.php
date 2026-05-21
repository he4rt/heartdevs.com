<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Enums;

enum TwitchEventSubType: string
{
    case StreamOnline = 'stream.online';
    case StreamOffline = 'stream.offline';
    case ChannelUpdate = 'channel.update';
    case ChannelFollow = 'channel.follow';
    case ChannelSubscribe = 'channel.subscribe';
    case ChannelSubscriptionGift = 'channel.subscription.gift';
    case ChannelSubscriptionMessage = 'channel.subscription.message';
    case ChannelSubscriptionEnd = 'channel.subscription.end';
    case ChannelCheer = 'channel.cheer';
    case ChannelRaid = 'channel.raid';
    case ChannelBan = 'channel.ban';
    case ChannelUnban = 'channel.unban';
    case ChannelModeratorAdd = 'channel.moderator.add';
    case ChannelModeratorRemove = 'channel.moderator.remove';
    case ChannelPointsRedemptionAdd = 'channel.channel_points_custom_reward_redemption.add';
    case ChannelPointsRedemptionUpdate = 'channel.channel_points_custom_reward_redemption.update';
    case ChannelPollBegin = 'channel.poll.begin';
    case ChannelPollProgress = 'channel.poll.progress';
    case ChannelPollEnd = 'channel.poll.end';
    case ChannelPredictionBegin = 'channel.prediction.begin';
    case ChannelPredictionProgress = 'channel.prediction.progress';
    case ChannelPredictionLock = 'channel.prediction.lock';
    case ChannelPredictionEnd = 'channel.prediction.end';
    case ChannelHypeTrainBegin = 'channel.hype_train.begin';
    case ChannelHypeTrainProgress = 'channel.hype_train.progress';
    case ChannelHypeTrainEnd = 'channel.hype_train.end';
    case ChannelGoalBegin = 'channel.goal.begin';
    case ChannelGoalProgress = 'channel.goal.progress';
    case ChannelGoalEnd = 'channel.goal.end';
    case ChannelShieldModeBegin = 'channel.shield_mode.begin';
    case ChannelShieldModeEnd = 'channel.shield_mode.end';
    case ChannelShoutoutCreate = 'channel.shoutout.create';
    case ChannelShoutoutReceive = 'channel.shoutout.receive';
    case ChannelAdBreakBegin = 'channel.ad_break.begin';
    case ChannelChatMessage = 'channel.chat.message';

    public function version(): string
    {
        return match ($this) {
            self::ChannelUpdate,
            self::ChannelFollow => '2',
            default => '1',
        };
    }

    /**
     * @return array<string, string>
     */
    public function condition(string $broadcasterId, ?string $userId = null): array
    {
        return match ($this) {
            self::ChannelFollow,
            self::ChannelShieldModeBegin,
            self::ChannelShieldModeEnd,
            self::ChannelShoutoutCreate,
            self::ChannelShoutoutReceive,
            self::ChannelChatMessage => [
                'broadcaster_user_id' => $broadcasterId,
                'user_id' => $userId ?? $broadcasterId,
            ],
            self::ChannelModeratorAdd,
            self::ChannelModeratorRemove => [
                'broadcaster_user_id' => $broadcasterId,
                'moderator_user_id' => $userId ?? $broadcasterId,
            ],
            self::ChannelRaid => [
                'to_broadcaster_user_id' => $broadcasterId,
            ],
            default => [
                'broadcaster_user_id' => $broadcasterId,
            ],
        };
    }
}
