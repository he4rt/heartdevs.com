<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

use He4rt\BotDiscord\DTO\VoiceChannelDTO;

final class LeftChannelAction
{
    public function execute(array $activeChannels, $user): void
    {
        foreach ($activeChannels as $index => $channel) {
            /** @var VoiceChannelDTO $channel */
            if (in_array($user, $channel->users)) {
                $activeChannels[$index]->users = array_values(array_filter($channel->users, fn ($userId) => $userId !== $user));
                $activeChannels[$index]->usersCount--;

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
