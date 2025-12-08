<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event as Events;
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
        $user = $state->user_id;
        $activeChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);
        $this->logger()->info('Channel Members:'.($state->channel?->members?->count() ?? 0));

        if (! is_null($channelId)) {
            $this->joinedChannel(
                channelId: $channelId,
                activeChannels: $activeChannels,
                user: $user
            );

            return;
        }

        $this->leavedChannel(
            activeChannels: $activeChannels,
            user: $user
        );
    }

    private function joinedChannel(string $channelId, array $activeChannels, $user): void
    {
        foreach ($activeChannels as $index => $channel) {
            if (isset($channel['channelId']) && $channel['channelId'] === $channelId) {
                $activeChannels[$index]['users'][] = $user;
                $activeChannels[$index]['usersCount']++;

                $activeChannels[$index]['lastJoinedAt'] = now();
                cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $activeChannels);
                break;
            }
        }
    }

    private function leavedChannel(array $activeChannels, $user): void
    {
        foreach ($activeChannels as $index => $channel) {

            if (in_array($user, $channel['users'])) {
                $activeChannels[$index]['users'] = array_values(array_filter($channel['users'], fn ($userId) => $userId !== $user));
                $activeChannels[$index]['usersCount']--;

                cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $activeChannels);
                break;
            }
        }
    }
}
