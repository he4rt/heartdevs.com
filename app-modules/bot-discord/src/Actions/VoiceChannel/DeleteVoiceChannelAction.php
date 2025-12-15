<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

use Discord\Discord;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;

final class DeleteVoiceChannelAction
{
    public function execute(Discord $discord): void
    {
        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);
        foreach ($channels as $index => $channel) {
            /** @var VoiceChannelDTO $channel */
            if ($channel->isEmpty() && $channel->isLongTermEmpty()) {
                $this->delete($channel->guildId, $channel->channelId, $index, $discord);
            }
        }
    }

    private function delete(string $guildId, string $channelId, int $arrayIndex, Discord $discord): void
    {
        $guild = $discord->guilds->get('id', $guildId);

        if ($guild && $guild->channels->has($channelId)) {
            $guild->channels->delete($channelId);
        }

        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);

        if (isset($channels[$arrayIndex])) {

            unset($channels[$arrayIndex]);

            $channels = array_filter($channels, fn (VoiceChannelDTO $channel) => $channel->channelId !== $channelId);
            $channels = array_values($channels);

            cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $channels);
        }
    }
}
