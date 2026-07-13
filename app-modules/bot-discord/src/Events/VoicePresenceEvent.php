<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event as Events;
use He4rt\Activity\Voice\Actions\RecordVoicePresence;
use He4rt\Activity\Voice\DTOs\RecordVoicePresenceDTO;
use He4rt\Activity\Voice\Queries\GetCurrentVoiceChannel;
use He4rt\BotDiscord\Actions\VoiceTransitionResolver;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Support\Facades\Log;
use Laracord\Events\Event;
use Throwable;

class VoicePresenceEvent extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::VOICE_STATE_UPDATE;

    /**
     * Handle the event.
     *
     * The previous channel is read from our own append-only log
     * (GetCurrentVoiceChannel), NOT from discord-php's `$oldState`, which is
     * rebuilt from a snapshot on every gateway reconnect (READY/GUILD_CREATE)
     * and would otherwise produce phantom, unpaired `left` rows.
     */
    public function handle(VoiceStateUpdate $state, Discord $discord, ?VoiceStateUpdate $oldState): void
    {
        if ($this->isBot($state)) {
            return;
        }

        try {
            $current = resolve(GetCurrentVoiceChannel::class)->handle(
                IdentityProvider::Discord,
                (string) $state->user_id,
            );

            // The resolver pairs each presence with its channel name: `joined` takes
            // the new channel (from the gateway), `left` the previous one (from our
            // log). $state->channel is null on a leave, hence the nullsafe read.
            $transitions = resolve(VoiceTransitionResolver::class)->resolve(
                oldChannelId: $current?->channelId,
                oldChannelName: $current?->channelName,
                newChannelId: $state->channel_id,
                newChannelName: $state->channel?->name,
            );

            if (blank($transitions)) {
                return;
            }

            // A move emits left+joined; persist them together so the log stays paired.
            resolve(RecordVoicePresence::class)->persistMany(
                RecordVoicePresenceDTO::makeMany(
                    provider: IdentityProvider::Discord,
                    externalAccountId: (string) $state->user_id,
                    transitions: $transitions,
                    username: ($state->member->user ?? $state->user)?->username,
                ),
            );
        } catch (Throwable $throwable) {
            Log::channel('bot-discord')->error('VoicePresenceEvent: failed to record voice presence', [
                'discord_user_id' => $state->user_id,
                'guild_id' => $state->guild_id,
                'channel_id' => $state->channel_id,
                'exception' => $throwable,
            ]);

            report($throwable);
        }
    }

    private function isBot(VoiceStateUpdate $state): bool
    {
        $user = $state->member->user ?? $state->user;

        if ($user === null) {
            return true;
        }

        return (bool) $user->bot;
    }
}
