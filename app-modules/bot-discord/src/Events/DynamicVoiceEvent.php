<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event as Events;
use He4rt\BotDiscord\Actions\JoiningChannelAction;
use He4rt\BotDiscord\Actions\LeftChannelAction;
use Laracord\Events\Event;

class DynamicVoiceEvent extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::VOICE_STATE_UPDATE;

    /**
     * Handle the event.
     */
    public function handle(VoiceStateUpdate $state, Discord $discord, ?VoiceStateUpdate $oldState): void
    {
        $channelId = $state->channel_id;
        $userId = $state->user_id;

        $activeChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);
        $this->logger()->info('Channel Members:'.($state->channel?->members?->count() ?? 0));

        $oldChannelId = $this->getUserLastChannel($userId);

        if ($this->isLeavingVoice($channelId, $oldChannelId)) {
            resolve(LeftChannelAction::class)->execute(
                activeChannels: $activeChannels,
                user: $userId
            );

            $this->clearUserLastChannel($userId);

            return;
        }

        if ($this->isMovingBetweenChannels($channelId, $oldChannelId)) {
            resolve(LeftChannelAction::class)->execute(
                activeChannels: $activeChannels,
                user: $userId
            );

            resolve(JoiningChannelAction::class)->execute(
                channelId: $channelId,
                activeChannels: $activeChannels,
                user: $userId
            );

            $this->setUserLastChannel($userId, $channelId);

            return;
        }

        if ($this->isJoiningChannel($channelId, $oldChannelId)) {
            resolve(JoiningChannelAction::class)->execute(
                channelId: $channelId,
                activeChannels: $activeChannels,
                user: $userId
            );
            $this->setUserLastChannel($userId, $channelId);

            return;
        }

        if ($this->isUpdatingInSameChannel($channelId, $oldChannelId)) {
            $this->setUserLastChannel($userId, $channelId);

            return;
        }
    }

    private function isMovingBetweenChannels(?string $newChannelId, ?string $oldChannelId): bool
    {
        return ! is_null($newChannelId)
            && ! is_null($oldChannelId)
            && $oldChannelId !== $newChannelId;
    }

    private function isLeavingVoice(?string $newChannelId, ?string $oldChannelId): bool
    {
        return is_null($newChannelId) && ! is_null($oldChannelId);
    }

    private function isJoiningChannel(?string $channelId, ?string $oldChannelId): bool
    {
        return ! is_null($channelId) && is_null($oldChannelId);
    }

    private function getUserLastChannel(string $userId): ?string
    {
        return cache()->tags(['voice_tracking'])->get('user_last_channel_'.$userId);
    }

    private function setUserLastChannel(string $userId, string $channelId): void
    {
        $ttl = 60 * 60 * 24;
        cache()->tags(['voice_tracking'])->put('user_last_channel_'.$userId, $channelId, $ttl);
    }

    private function clearUserLastChannel(string $userId): void
    {
        cache()->tags(['voice_tracking'])->forget('user_last_channel_'.$userId);
    }

    private function isUpdatingInSameChannel($newChannelId, ?string $oldChannelId): bool
    {
        return ! is_null($newChannelId) && $oldChannelId === $newChannelId;
    }
}
