<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Tasks;

use Illuminate\Support\Facades\Date;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Support\Facades\Cache;
use Laracord\Tasks\Task;

class DynamicVoiceTask extends Task
{
    /**
     * The task interval.
     */
    protected int $interval = 30;

    /**
     * Determine if the task handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * Handle the task.
     */
    public function handle(Interaction $interaction): void
    {
        $channelsToDelete = [];
        $channelID = null;

        $channels = $this->discord()->guilds->first()->channels->filter(fn (Channel $channel) => $channel->type === 2);

        foreach ($channels as $channel) {

            /** @var Channel $channel */
            $channelID = $channel->id;
            $cacheKey = sprintf('channel_%s', $channelID);
            $timestampKey = sprintf('channel_%s_timestamp', $channelID);

            if ($channel->getMembersAttribute()->count() > 0) {
                Cache::tags(['active_voice_channels'])->put($cacheKey, $channelID, 300);
                Cache::tags(['inactive_voice_channels'])->forget($timestampKey);

                continue;
            }

            Cache::tags(['inactive_voice_channels'])->put($cacheKey, $channelID, 300);

            if (! Cache::tags(['inactive_voice_channels'])->has($timestampKey)) {
                Cache::tags(['inactive_voice_channels'])->put($timestampKey, now(), 300);

                continue;
            }

            $inactiveSince = Cache::tags(['inactive_voice_channels'])->get($timestampKey);

            if (! $inactiveSince) {
                continue;
            }

            $inactiveSince = Date::parse($inactiveSince);

            $diffInSeconds = abs(now()->diffInSeconds($inactiveSince));
            if ($diffInSeconds >= 10) {
                $channelsToDelete[] = $channelID;
            }
        }

        dump($channelsToDelete);

        foreach ($channelsToDelete as $channelId) {
            $interaction->guild->channels->delete($channelId);
        }
    }
}
