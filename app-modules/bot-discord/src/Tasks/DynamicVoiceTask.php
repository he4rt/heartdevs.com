<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Tasks;

use Laracord\Tasks\Task;

class DynamicVoiceTask extends Task
{
    /**
     * The task interval.
     */
    protected int $interval = 3;

    /**
     * Determine if the task handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * Handle the task.
     */
    public function handle(): void
    {
        dump(cache()->get('active_voice_channels_keys'));

        $channels = cache()->get('active_voice_channels_keys', []);
        foreach ($channels as $channel) {
            if ($channel['usersCount'] === 0 && abs($channel['lastJoinedAt']->diffInSeconds(now())) >= 20) {
                //                $this->discord()->getChannel($channel['channelId'])->delete();
                //                DeleteVoiceDiscord::dispatch( $channel['channelId']);
            }
        }
    }
}
