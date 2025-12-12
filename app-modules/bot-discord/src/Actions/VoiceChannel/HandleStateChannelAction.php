<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

final class HandleStateChannelAction
{
    public function execute(int|string $userId, ?string $channelId): void
    {
        $activeChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);
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

    private function isUpdatingInSameChannel(?string $newChannelId, ?string $oldChannelId): bool
    {
        return ! is_null($newChannelId) && $oldChannelId === $newChannelId;
    }
}
