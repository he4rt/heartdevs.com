<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event as Events;
use He4rt\BotDiscord\Actions\VoiceChannel\HandleStateChannelAction;
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
        $userId = $state->user_id;

        resolve(HandleStateChannelAction::class)->execute(
            userId: $userId,
            channelId: $channelId,
        );
    }
}
