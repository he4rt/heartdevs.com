<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

use He4rt\BotDiscord\DTO\VoiceChannelDTO;

final class JoiningChannelAction
{
    /**
     * @param  array<string, mixed>  $activeChannels
     */
    public function execute(string $channelId, array $activeChannels, string $user): void
    {
        foreach ($activeChannels as $index => $channel) {
            /** @var VoiceChannelDTO $channel */
            if ($channel->channelId === $channelId) {
                $activeChannels[$index]->users[] = $user;
                $activeChannels[$index]->usersCount++;

                $activeChannels[$index]->lastJoinedAt = now();
                $this->saveActiveChannels($activeChannels);
                break;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $activeChannels
     */
    private function saveActiveChannels(array $activeChannels): void
    {
        cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $activeChannels);
    }
}
