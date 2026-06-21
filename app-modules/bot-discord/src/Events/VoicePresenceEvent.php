<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event as Events;
use He4rt\Activity\Voice\Actions\RecordVoicePresence;
use He4rt\Activity\Voice\DTOs\RecordVoicePresenceDTO;
use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\Queries\GetCurrentVoiceChannel;
use He4rt\BotDiscord\Actions\ResolveDiscordTenant;
use He4rt\BotDiscord\Actions\VoiceTransitionResolver;
use He4rt\BotDiscord\ValueObjects\VoiceTransition;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Laracord\Events\Event;

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

        $tenant = resolve(ResolveDiscordTenant::class)->handle((string) $state->guild_id);

        $current = resolve(GetCurrentVoiceChannel::class)->handle(
            $tenant->tenant_id,
            IdentityProvider::Discord,
            (string) $state->user_id,
        );

        $transitions = resolve(VoiceTransitionResolver::class)->resolve(
            $current?->channelId,
            $state->channel_id,
        );

        if ($transitions === []) {
            return;
        }

        foreach ($transitions as $transition) {
            resolve(RecordVoicePresence::class)->persist(new RecordVoicePresenceDTO(
                tenantId: $tenant->tenant_id,
                provider: IdentityProvider::Discord,
                externalAccountId: (string) $state->user_id,
                presence: $transition->presence,
                channelName: $this->resolveChannelName($transition, $state, $current?->channelName),
                channelId: $transition->channelId,
                username: ($state->member->user ?? $state->user)?->username,
            ));
        }
    }

    private function resolveChannelName(VoiceTransition $transition, VoiceStateUpdate $state, ?string $previousChannelName): string
    {
        // A `joined` always targets the new channel ($state is fresh for it);
        // a `left` targets the previous channel, whose name lives in our log.
        if ($transition->presence === VoicePresenceEnum::Joined) {
            return $state->channel->name ?? $transition->channelId;
        }

        return $previousChannelName ?? $transition->channelId;
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
