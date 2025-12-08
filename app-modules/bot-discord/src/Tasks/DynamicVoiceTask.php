<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Tasks;

use Laracord\Tasks\Task;

class DynamicVoiceTask extends Task
{
    /**
     * The task interval.
     */
    protected int $interval = 5;

    /**
     * Determine if the task handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * Handle the task.
     */
    public function handle(): void
    {
        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);
        foreach ($channels as $index => $channel) {
            if ($channel['usersCount'] === 0 && abs($channel['lastJoinedAt']->diffInSeconds(now())) >= 20) {
                $this->delete($channel['guildId'], $channel['channelId'], $index);
            }
        }
    }

    public function delete(string $guildId, string $channelId, int $arrayIndex): void
    {
        $guild = $this->discord()->guilds->get('id', $guildId);

        if ($guild && $guild->channels->has($channelId)) {
            $guild->channels->delete($channelId);
        }

        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);

        if (isset($channels[$arrayIndex])) {

            unset($channels[$arrayIndex]);

            $channels = array_filter($channels, fn(array $channel) => $channel['channelId'] !== $channelId);
            $channels = array_values($channels);

            dump($channels);
            cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $channels);
        }

        $this->logger()->info('Canal '.$channelId.' removido do cache.');
    }
}
