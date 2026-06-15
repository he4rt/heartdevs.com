<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

use He4rt\BotDiscord\DTO\VoiceChannelDTO;

final class LeftChannelAction
{
    /**
     * @param  array<string, VoiceChannelDTO>  $activeChannels
     */
    public function execute(array $activeChannels, string $user): void
    {
        foreach ($activeChannels as $index => $channel) {
            /** @var VoiceChannelDTO $channel */
            if (in_array($user, $channel->users)) {
                $activeChannels[$index]->users = array_values(array_filter($channel->users, fn (string $userId) => $userId !== $user));
                $activeChannels[$index]->usersCount--;

                $this->saveActiveChannels($activeChannels);
                break;
            }
        }
    }

    /**
     * @param  array<string, VoiceChannelDTO>  $activeChannels
     */
    private function saveActiveChannels(array $activeChannels): void
    {
        cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $activeChannels);
    }
}
