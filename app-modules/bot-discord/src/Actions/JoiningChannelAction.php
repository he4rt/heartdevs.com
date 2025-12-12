<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions;

use He4rt\BotDiscord\DTO\VoiceChannelDTO;

final class JoiningChannelAction
{
    public function execute(string $channelId, array $activeChannels, $user): void
    {
        foreach ($activeChannels as $index => $channel) {
            /** @var VoiceChannelDTO $channel */
            if (isset($channel->channelId) && $channel->channelId === $channelId) {
                $activeChannels[$index]->users[] = $user;
                $activeChannels[$index]->usersCount++;

                $activeChannels[$index]->lastJoinedAt = now();
                $this->saveActiveChannels($activeChannels);
                break;
            }
        }
    }

    private function saveActiveChannels(array $activeChannels): void
    {
        cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $activeChannels);
    }
}
